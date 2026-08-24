<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Security;

final class Validators
{
    public static function nationalId(string $value): bool
    {
        $value = preg_replace('/\D+/', '', self::latinDigits($value));
        if (!is_string($value) || strlen($value) !== 10 || preg_match('/^(\d)\1{9}$/', $value)) return false;
        $check = (int)$value[9];
        $sum = 0;
        for ($i=0; $i<9; $i++) $sum += ((int)$value[$i]) * (10-$i);
        $r = $sum % 11;
        return ($r < 2 && $check === $r) || ($r >= 2 && $check === 11-$r);
    }

    public static function iranIban(string $value): bool
    {
        $value = strtoupper(preg_replace('/\s+/', '', self::latinDigits($value)));
        if (!preg_match('/^IR\d{24}$/', $value)) return false;
        $rearranged = substr($value, 4) . '1827' . substr($value, 2, 2);
        $mod = 0;
        foreach (str_split($rearranged) as $digit) $mod = ($mod * 10 + (int)$digit) % 97;
        return $mod === 1;
    }

    public static function mobile(string $value): bool
    {
        $v = preg_replace('/\D+/', '', self::latinDigits($value));
        return (bool)preg_match('/^(?:98|0)?9\d{9}$/', (string)$v);
    }


    public static function gregorianDate(string $value): bool
    {
        $value = self::latinDigits(trim($value));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public static function jalaliDate(string $value): bool
    {
        $value = self::latinDigits(trim($value));
        if (!preg_match('~^(\d{4})/(\d{2})/(\d{2})$~', $value, $m)) return false;
        $year=(int)$m[1]; $month=(int)$m[2]; $day=(int)$m[3];
        if ($year < 1 || $month < 1 || $month > 12 || $day < 1) return false;
        $max = $month <= 6 ? 31 : ($month <= 11 ? 30 : (self::jalaliLeapYear($year) ? 30 : 29));
        return $day <= $max;
    }

    private static function jalaliLeapYear(int $year): bool
    {
        // Same 2820-compatible break-point algorithm used by common Jalaali implementations.
        $breaks=[-61,9,38,199,426,686,756,818,1111,1181,1210,1635,2060,2097,2192,2262,2324,2394,2456,3178];
        $jp=$breaks[0]; $jump=0;
        foreach (array_slice($breaks,1) as $jm) {
            $jump=$jm-$jp;
            if ($year<$jm) break;
            $jp=$jm;
        }
        $n=$year-$jp;
        if ($jump-$n<6) $n=$n-$jump+33*intdiv($jump+4,33);
        $leap=(($n+1)%33-1)%4;
        if ($leap<0) $leap+=4;
        return $leap===0;
    }

    public static function latinDigits(string $value): string
    {
        return strtr($value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }
}
