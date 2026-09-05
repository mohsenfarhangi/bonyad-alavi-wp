<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

use RuntimeException;

/**
 * Registry/router for SMS providers.
 *
 * A provider can be selected globally and overridden per Action. Availability
 * and supported modes are evaluated at runtime so integrations can follow the
 * state/configuration of another WordPress plugin without copying credentials.
 */
final class SmsProviderRegistry
{
    public const USE_DEFAULT = 'default';

    /** @var array<string,array{label:string,provider:SmsProviderInterface,modes:callable,available:callable,status:callable}> */
    private array $providers = [];

    public function __construct(
        private string $defaultProvider = 'melipayamak',
        private bool $enabled = true
    ) {
        $this->defaultProvider = sanitize_key($this->defaultProvider);
        if ($this->defaultProvider === '') $this->defaultProvider = 'melipayamak';
    }

    /**
     * @param list<string>|callable():array $modes
     * @param bool|callable():bool $available
     * @param array<string,mixed>|callable():array $status
     */
    public function register(
        string $key,
        string $label,
        SmsProviderInterface $provider,
        array|callable $modes = ['free','pattern'],
        bool|callable $available = true,
        array|callable $status = []
    ): void {
        $key = sanitize_key($key);
        if ($key === '' || $key === self::USE_DEFAULT) {
            throw new RuntimeException('Invalid SMS provider key.');
        }

        $this->providers[$key] = [
            'label'=>$label,
            'provider'=>$provider,
            'modes'=>is_callable($modes) ? $modes : static fn(): array => $modes,
            'available'=>is_callable($available) ? $available : static fn(): bool => $available,
            'status'=>is_callable($status) ? $status : static fn(): array => $status,
        ];
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function setDefaultProvider(string $key): void
    {
        $key = sanitize_key($key);
        if ($key !== '') $this->defaultProvider = $key;
    }

    public function defaultProvider(): string
    {
        return $this->defaultProvider;
    }

    public function effectiveKey(string $requested): string
    {
        $requested = sanitize_key($requested);
        return $requested === '' || $requested === self::USE_DEFAULT ? $this->defaultProvider : $requested;
    }

    public function resolve(string $requested, string $mode): SmsProviderInterface
    {
        if (!$this->enabled) {
            throw new RuntimeException('سرویس پیامک در تنظیمات Alavi Form Engine غیرفعال است.');
        }

        $key = $this->effectiveKey($requested);
        if (!isset($this->providers[$key])) {
            throw new RuntimeException('Provider پیامک انتخاب‌شده در دسترس نیست: '.$key);
        }
        if (!$this->isAvailable($key)) {
            $label = $this->label($key);
            throw new RuntimeException('Provider پیامک «'.$label.'» در حال حاضر فعال/قابل استفاده نیست.');
        }
        if (!$this->supportsMode($key, $mode)) {
            $label = $this->label($key);
            throw new RuntimeException('روش ارسال «'.$mode.'» توسط Provider «'.$label.'» پشتیبانی نمی‌شود.');
        }

        return $this->providers[$key]['provider'];
    }

    public function has(string $key): bool
    {
        return isset($this->providers[sanitize_key($key)]);
    }

    public function label(string $key): string
    {
        $key = sanitize_key($key);
        return (string)($this->providers[$key]['label'] ?? $key);
    }

    public function isAvailable(string $key): bool
    {
        $key = sanitize_key($key);
        if (!isset($this->providers[$key])) return false;
        try {
            return (bool)($this->providers[$key]['available'])();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    public function supportedModes(string $key): array
    {
        $key = $this->effectiveKey($key);
        if (!isset($this->providers[$key])) return [];
        try {
            $modes = (array)($this->providers[$key]['modes'])();
        } catch (\Throwable) {
            return [];
        }
        $modes = array_values(array_unique(array_filter(array_map('sanitize_key', $modes))));
        return array_values(array_intersect($modes, ['free','pattern']));
    }

    public function supportsMode(string $key, string $mode): bool
    {
        return in_array(sanitize_key($mode), $this->supportedModes($key), true);
    }

    /** @return array<string,string> */
    public function providerOptions(): array
    {
        $options = [];
        foreach ($this->providers as $key => $definition) {
            $label = (string)$definition['label'];
            if (!$this->isAvailable($key)) $label .= ' — غیرفعال/در دسترس نیست';
            $options[$key] = $label;
        }
        return $options;
    }

    /** @return array<string,string> */
    public function actionOptions(): array
    {
        $options = [self::USE_DEFAULT=>'پیش‌فرض سراسری — '.$this->label($this->defaultProvider)];
        foreach ($this->providers as $key => $definition) {
            $label = (string)$definition['label'];
            if (!$this->isAvailable($key)) $label .= ' — غیرفعال/در دسترس نیست';
            $options[$key] = $label;
        }
        return $options;
    }

    /** @return list<string> */
    public function unavailableKeys(): array
    {
        $keys = [];
        foreach (array_keys($this->providers) as $key) {
            if (!$this->isAvailable($key)) $keys[] = $key;
        }
        return $keys;
    }

    /** @return array<string,list<string>> */
    public function actionModeMap(): array
    {
        $map = [self::USE_DEFAULT=>$this->supportedModes($this->defaultProvider)];
        foreach (array_keys($this->providers) as $key) $map[$key] = $this->supportedModes($key);
        return $map;
    }

    /** @return array<string,mixed> */
    public function status(string $key): array
    {
        $key = sanitize_key($key);
        if (!isset($this->providers[$key])) return ['available'=>false];
        try {
            $status = (array)($this->providers[$key]['status'])();
        } catch (\Throwable $e) {
            $status = ['error'=>$e->getMessage()];
        }
        return array_replace([
            'key'=>$key,
            'label'=>$this->label($key),
            'available'=>$this->isAvailable($key),
            'modes'=>$this->supportedModes($key),
        ], $status);
    }

    /** @return array<string,array<string,mixed>> */
    public function statuses(): array
    {
        $result = [];
        foreach (array_keys($this->providers) as $key) $result[$key] = $this->status($key);
        return $result;
    }

    public static function single(SmsProviderInterface $provider): self
    {
        $registry = new self('provider', true);
        $registry->register('provider', 'Provider پیامک', $provider, ['free','pattern']);
        return $registry;
    }
}
