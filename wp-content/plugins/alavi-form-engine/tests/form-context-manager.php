<?php
declare(strict_types=1);

namespace {
    final class WP_Error
    {
        public function __construct(private string $code = '', private string $message = '') {}
        public function get_error_code(): string { return $this->code; }
        public function get_error_message(): string { return $this->message; }
    }

    function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
    function sanitize_key(string $key): string {
        $key = strtolower($key);
        return preg_replace('/[^a-z0-9_\-]/', '', $key) ?? '';
    }
    function wp_salt(string $scheme = 'auth'): string { return 'afe-test-context-secret'; }
    function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }

    spl_autoload_register(static function(string $class): void {
        $prefix = 'BonyadAlavi\\FormEngine\\';
        if (!str_starts_with($class, $prefix)) return;
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_readable($file)) require_once $file;
    });
}

namespace {
    use BonyadAlavi\FormEngine\Context\FormContextProviderInterface;
    use BonyadAlavi\FormEngine\Context\FormContextRegistry;
    use BonyadAlavi\FormEngine\Context\FormContextManager;

    final class ContextProviderStub implements FormContextProviderInterface
    {
        public bool $active = true;

        public function key(): string { return 'sample'; }
        public function label(): string { return 'Sample provider'; }
        public function supportedSources(): array { return ['current_page','fixed','query_parameter']; }
        public function fixedOptions(): array { return ['42'=>'Example record']; }

        public function resolve(array $settings, string $formSlug): array|WP_Error
        {
            $source = (string)($settings['source'] ?? '');
            if ($source === 'fixed') {
                return ['record_id'=>(int)($settings['fixed_value'] ?? 0)];
            }
            if ($source === 'query_parameter') {
                $parameter = (string)($settings['query_parameter'] ?? '');
                return ['record_id'=>(int)($_GET[$parameter] ?? 0)];
            }
            return ['record_id'=>42];
        }

        public function validate(array $context, string $formSlug): array|WP_Error
        {
            if (!$this->active || (int)($context['record_id'] ?? 0) !== 42) {
                return new WP_Error('context_unavailable', 'Context is no longer available.');
            }
            return ['record_id'=>42,'record_label'=>'Example record'];
        }
    }

    $registry = new FormContextRegistry();
    $provider = new ContextProviderStub();
    $registry->register($provider);
    $manager = new FormContextManager($registry);

    $_GET['support_category'] = '42';
    $issued = $manager->issue('sample-form', [
        'provider'=>'sample',
        'source'=>'query_parameter',
        'identifier_type'=>'id',
        'query_parameter'=>'support_category',
    ]);

    $checks = [];
    $checks['signed-token-issued'] = is_array($issued) && !empty($issued['token']);
    $checks['canonical-context-returned'] = is_array($issued) && ($issued['context']['record_label'] ?? '') === 'Example record';
    $verified = is_array($issued) ? $manager->verify('sample-form', $issued['token']) : null;
    $checks['token-verifies'] = is_array($verified) && ($verified['record_id'] ?? 0) === 42;
    $checks['missing-token-is-empty-context'] = $manager->verify('sample-form', '') === [];
    $checks['wrong-form-rejected'] = is_array($issued) && is_wp_error($manager->verify('another-form', $issued['token']));

    $tampered = is_array($issued) ? $issued['token'] : '';
    if ($tampered !== '') {
        $tampered[0] = $tampered[0] === 'a' ? 'b' : 'a';
    }
    $checks['tampered-token-rejected'] = $tampered !== '' && is_wp_error($manager->verify('sample-form', $tampered));
    $checks['unsupported-source-rejected'] = is_wp_error($manager->issue('sample-form', [
        'provider'=>'sample','source'=>'untrusted_source',
    ]));

    $provider->active = false;
    $checks['provider-revalidated-at-submit'] = is_array($issued) && is_wp_error($manager->verify('sample-form', $issued['token']));
    $fixedOptions = $registry->fixedOptions();
    $checks['fixed-options-are-provider-scoped'] = ($fixedOptions['sample|42'] ?? '') === 'Sample provider — Example record';

    foreach ($checks as $name => $ok) {
        echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
        if (!$ok) exit(1);
    }
}
