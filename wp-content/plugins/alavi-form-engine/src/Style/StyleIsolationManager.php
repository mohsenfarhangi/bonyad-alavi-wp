<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Style;

final class StyleIsolationManager
{
    public const MODE_DEFAULT = 'default';
    public const MODE_STRONG = 'strong';
    public const MODE_DISABLED = 'disabled';

    /** @return array<string,string> */
    public static function modes(): array
    {
        return [
            self::MODE_STRONG => 'ایزوله‌سازی قوی',
            self::MODE_DEFAULT => 'ایزوله‌سازی معمولی',
            self::MODE_DISABLED => 'غیرفعال',
        ];
    }

    public function modeForForm(array $form = []): string
    {
        $formMode = (string)($form['settings']['style_isolation'] ?? '');
        if ($formMode !== '' && array_key_exists($formMode, self::modes())) {
            return $formMode;
        }

        $settings = (array)get_option('afe_settings', []);
        return $this->normalize((string)($settings['style_isolation'] ?? self::MODE_STRONG));
    }

    public function normalize(string $mode): string
    {
        return array_key_exists($mode, self::modes()) ? $mode : self::MODE_STRONG;
    }

    public function shellClass(array $form = []): string
    {
        return 'afe-shell afe-isolation-' . $this->modeForForm($form);
    }
}
