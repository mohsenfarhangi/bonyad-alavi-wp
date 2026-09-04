<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\InputMask;

use BonyadAlavi\FormEngine\Security\Validators;

final class InputMaskPattern
{
    public const MAX_PATTERN_LENGTH = 80;
    public const MAX_SLOTS = 64;

    /**
     * Pattern syntax:
     * 9 = digit, A = Unicode letter, * = Unicode letter or digit.
     * Backslash escapes the next character and makes it literal.
     */
    public static function isValid(string $pattern): bool
    {
        $nodes = self::parse($pattern);
        if ($nodes === null) return false;
        $slots = 0;
        foreach ($nodes as $node) if ($node['type'] === 'slot') $slots++;
        return $slots > 0 && $slots <= self::MAX_SLOTS;
    }

    /** @return list<array{type:string,value:string}>|null */
    public static function parse(string $pattern): ?array
    {
        $pattern = trim($pattern);
        if ($pattern === '' || strlen($pattern) > self::MAX_PATTERN_LENGTH || preg_match('/[\x00-\x1F\x7F]/', $pattern)) return null;
        $chars = self::chars($pattern);
        $out = [];
        $escaped = false;
        foreach ($chars as $char) {
            if ($escaped) {
                $out[] = ['type'=>'literal','value'=>$char];
                $escaped = false;
                continue;
            }
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            if (in_array($char, ['9','A','*'], true)) $out[] = ['type'=>'slot','value'=>$char];
            else $out[] = ['type'=>'literal','value'=>$char];
        }
        if ($escaped) return null;
        return $out;
    }

    public static function normalize(string $value, string $pattern): string
    {
        $nodes = self::parse($pattern);
        if ($nodes === null) return Validators::latinDigits(trim($value));
        $chars = self::chars(Validators::latinDigits(trim($value)));
        $index = 0;
        $out = '';
        foreach ($nodes as $node) {
            if ($node['type'] === 'literal') {
                if (($chars[$index] ?? null) === $node['value']) $index++;
                continue;
            }
            while (isset($chars[$index])) {
                $char = $chars[$index++];
                if (self::matches($char, $node['value'])) {
                    $out .= $char;
                    break;
                }
            }
        }
        return $out;
    }

    public static function format(string $value, string $pattern): string
    {
        $nodes = self::parse($pattern);
        if ($nodes === null) return $value;
        $clean = self::normalize($value, $pattern);
        $chars = self::chars($clean);
        $slotIndex = 0;
        $slotCount = count(array_filter($nodes, static fn(array $node): bool => $node['type'] === 'slot'));
        $out = '';
        foreach ($nodes as $node) {
            if ($node['type'] === 'slot') {
                if (!isset($chars[$slotIndex])) break;
                $out .= $chars[$slotIndex++];
                continue;
            }
            if ($slotIndex > 0 && $slotIndex < $slotCount && isset($chars[$slotIndex])) $out .= $node['value'];
        }
        return $out;
    }

    public static function slotCount(string $pattern): int
    {
        $nodes = self::parse($pattern) ?? [];
        return count(array_filter($nodes, static fn(array $node): bool => $node['type'] === 'slot'));
    }

    public static function displayLength(string $pattern): int
    {
        $nodes = self::parse($pattern) ?? [];
        return count($nodes);
    }

    public static function suggestedInputMode(string $pattern): string
    {
        $nodes = self::parse($pattern) ?? [];
        $slots = array_values(array_filter($nodes, static fn(array $node): bool => $node['type'] === 'slot'));
        if ($slots !== [] && count(array_filter($slots, static fn(array $node): bool => $node['value'] === '9')) === count($slots)) return 'numeric';
        return 'text';
    }

    private static function matches(string $char, string $token): bool
    {
        return match ($token) {
            '9' => preg_match('/^[0-9]$/u', $char) === 1,
            'A' => preg_match('/^\p{L}$/u', $char) === 1,
            '*' => preg_match('/^[\p{L}0-9]$/u', $char) === 1,
            default => false,
        };
    }

    /** @return list<string> */
    private static function chars(string $value): array
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($chars) ? $chars : str_split($value);
    }
}
