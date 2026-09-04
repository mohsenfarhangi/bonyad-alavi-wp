<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use RuntimeException;
use Throwable;

final class ActionManager
{
    public function __construct(
        private readonly ActionRegistry $registry,
        private readonly ActionExecutionRepository $executions
    ) {}

    /**
     * Backward-compatible developer registration hook. New integrations should
     * prefer ActionRegistry::register() so the admin UI can also read metadata.
     */
    public function register(string $name, ActionInterface|callable $action): void
    {
        $this->registry->registerHandler($name, $action);
    }

    public function registry(): ActionRegistry
    {
        return $this->registry;
    }

    /**
     * Backward-compatible API: callers that only need errors can keep using run().
     * New lifecycle code should use runWithResult() so Redirect/runtime outputs are
     * available after all server-side actions have executed.
     *
     * @return array<string, string> action_key => error message
     */
    public function run(array $definitions, ActionContext $context): array
    {
        return $this->runWithResult($definitions, $context)->errors;
    }

    public function runWithResult(array $definitions, ActionContext $context): ActionRunResult
    {
        $errors = [];
        $runtime = $context->runtime ?? new ActionRuntime();
        if ($context->runtime === null) $context = $context->withRuntime($runtime);
        $definitions = $this->flattenDefinitions($definitions);

        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                if (!is_string($definition)) continue;
                $definition = ['type'=>$definition];
            }

            if (array_key_exists('enabled', $definition) && empty($definition['enabled'])) continue;

            $type = sanitize_key((string)($definition['type'] ?? ''));
            if ($type === '' || !$this->registry->has($type)) continue;

            $events = $this->definitionEvents($definition);
            if ($events !== [] && !in_array($context->event, $events, true)) continue;

            if (!empty($definition['when']) && !$this->matches((array)$definition['when'], $context->data)) continue;

            $actionKey = $this->actionKey($definition, $type, (int)$index);
            $policy = $this->executionPolicy($definition);
            $guardScope = $this->guardScope($policy, $context->event);

            if ($guardScope !== null && !$this->executions->claimOnce($context->submissionId, $guardScope, $actionKey)) {
                continue;
            }

            $config = isset($definition['config']) && is_array($definition['config'])
                ? $definition['config']
                : $this->legacyConfig($definition);

            $logId = $this->executions->start(
                $context->submissionId,
                $context->formSlug,
                $context->event,
                $actionKey,
                $type,
                [
                    'execution_policy'=>$policy,
                    'on_error'=>(string)($definition['on_error'] ?? 'continue'),
                    'retry'=>$runtime->isRetry(),
                ]
            );

            try {
                $action = $this->registry->handler($type);
                if ($action instanceof ActionInterface) {
                    $action->handle($context, $config);
                } elseif (is_callable($action)) {
                    $action($context, $config);
                } else {
                    throw new RuntimeException('Action handler is not executable.');
                }

                $runtime->recordExecution($actionKey);
                $this->executions->finish($logId, 'success');
                if ($guardScope !== null) {
                    $this->executions->markOnce($context->submissionId, $guardScope, $actionKey, 'success');
                }
                do_action('afe_action_completed', $type, $actionKey, $context, $config);
            } catch (Throwable $e) {
                $runtime->recordExecution($actionKey);
                $errors[$actionKey] = $e->getMessage();
                $this->executions->finish($logId, 'failed', $e->getMessage());
                if ($guardScope !== null) {
                    $this->executions->markOnce($context->submissionId, $guardScope, $actionKey, 'failed');
                }
                do_action('afe_action_failed', $type, $actionKey, $e, $context, $config);

                if (sanitize_key((string)($definition['on_error'] ?? 'continue')) === 'stop') break;
            }
        }

        return new ActionRunResult($errors, $runtime);
    }

    /**
     * Explicit administrator retry. It executes the current saved definition with
     * the same event key and action_key as the failed log. Once guards are only
     * released after the action still exists and its conditions still match.
     */
    public function retry(array $definitions, ActionContext $context, string $requestedActionKey): ActionRunResult
    {
        $requestedActionKey = sanitize_key($requestedActionKey);
        if ($requestedActionKey === '') throw new RuntimeException('Action key برای Retry معتبر نیست.');

        $flat = $this->flattenDefinitions($definitions);
        foreach ($flat as $index => $definition) {
            if (!is_array($definition)) {
                if (!is_string($definition)) continue;
                $definition = ['type'=>$definition];
            }
            $type = sanitize_key((string)($definition['type'] ?? ''));
            if ($type === '' || !$this->registry->has($type)) continue;
            $actionKey = $this->actionKey($definition, $type, (int)$index);
            if (!hash_equals($requestedActionKey, $actionKey)) continue;

            if (array_key_exists('enabled', $definition) && empty($definition['enabled'])) {
                throw new RuntimeException('این Action در تنظیمات فعلی غیرفعال است.');
            }
            $actionDefinition = $this->registry->get($type);
            if (!$actionDefinition || !$actionDefinition->supportsRetry) {
                throw new RuntimeException('Retry مدیریتی برای این نوع Action مجاز نیست.');
            }
            $events = $this->definitionEvents($definition);
            if ($events !== [] && !in_array($context->event, $events, true)) {
                throw new RuntimeException('Event فعلی Action با Event ثبت‌شده در Log مطابقت ندارد.');
            }
            if (!empty($definition['when']) && !$this->matches((array)$definition['when'], $context->data)) {
                throw new RuntimeException('شرایط Conditional Logic این Action دیگر برقرار نیست.');
            }

            $policy = $this->executionPolicy($definition);
            $guardScope = $this->guardScope($policy, $context->event);
            if ($guardScope !== null) {
                $this->executions->releaseOnceForRetry($context->submissionId, $guardScope, $actionKey);
            }

            $runtime = new ActionRuntime(true);
            $result = $this->runWithResult([$definition], $context->withRuntime($runtime));
            if (!in_array($actionKey, $result->executedActionKeys(), true)) {
                throw new RuntimeException('Action برای Retry اجرا نشد.');
            }
            return $result;
        }

        throw new RuntimeException('Action با action_key ثبت‌شده در تنظیمات فعلی فرم پیدا نشد.');
    }

    /**
     * Supports both the current flat code definition and the admin UI structure:
     * [['event'=>'submission.submitted','actions'=>[...]]].
     */
    private function flattenDefinitions(array $definitions): array
    {
        $flat = [];
        foreach ($definitions as $definition) {
            if (is_array($definition) && isset($definition['actions']) && is_array($definition['actions'])) {
                $event = $this->canonicalEvent((string)($definition['event'] ?? ''));
                foreach ($definition['actions'] as $action) {
                    if (!is_array($action)) continue;
                    if ($event !== '' && empty($action['on']) && empty($action['events']) && empty($action['event'])) {
                        $action['on'] = [$event];
                    }
                    $flat[] = $action;
                }
                continue;
            }
            $flat[] = $definition;
        }
        return $flat;
    }

    /** @return list<string> */
    private function definitionEvents(array $definition): array
    {
        $hasExplicitEvent = array_key_exists('events', $definition) || array_key_exists('on', $definition) || array_key_exists('event', $definition);
        $raw = $definition['events'] ?? $definition['on'] ?? $definition['event'] ?? ['submission.submitted'];
        if (!$hasExplicitEvent) $raw = ['submission.submitted'];
        $raw = is_array($raw) ? $raw : [$raw];
        $events = [];
        foreach ($raw as $event) {
            $canonical = $this->canonicalEvent((string)$event);
            if ($canonical !== '') $events[] = $canonical;
        }
        return array_values(array_unique($events));
    }

    private function canonicalEvent(string $event): string
    {
        $event = trim($event);
        return match ($event) {
            'created' => 'submission.created',
            'updated' => 'submission.updated',
            'submitted' => 'submission.submitted',
            'draft', 'draft_saved' => 'submission.draft_saved',
            default => $event,
        };
    }

    private function actionKey(array $definition, string $type, int $index): string
    {
        $key = sanitize_key((string)($definition['action_key'] ?? ''));
        if ($key !== '') return substr($key, 0, 80);

        $encoded = wp_json_encode($definition, JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded) || $encoded === '') return 'legacy_' . $type . '_' . $index;
        return substr('legacy_' . $type . '_' . substr(hash('sha256', $encoded), 0, 12), 0, 80);
    }

    private function executionPolicy(array $definition): string
    {
        $policy = sanitize_key((string)($definition['execution_policy'] ?? 'always'));
        return in_array($policy, ['always','once_per_submission','first_in_cycle'], true) ? $policy : 'always';
    }

    private function guardScope(string $policy, string $event): ?string
    {
        return match ($policy) {
            'once_per_submission' => '__submission__',
            'first_in_cycle' => $event,
            default => null,
        };
    }

    private function legacyConfig(array $definition): array
    {
        $config = $definition;
        foreach (['type','action_key','enabled','on','events','event','when','execution_policy','on_error','actions'] as $reserved) {
            unset($config[$reserved]);
        }
        return $config;
    }

    private function matches(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            if (!is_array($condition)) continue;
            $actual = $this->valueByPath($data, (string)($condition['field'] ?? ''));
            $expected = $condition['value'] ?? null;
            $operator = (string)($condition['operator'] ?? '=');

            $ok = match ($operator) {
                '=','==' => (string)$actual === (string)$expected,
                '!=' => (string)$actual !== (string)$expected,
                '>' => (float)$actual > (float)$expected,
                '>=' => (float)$actual >= (float)$expected,
                '<' => (float)$actual < (float)$expected,
                '<=' => (float)$actual <= (float)$expected,
                'in' => in_array((string)$actual, array_map('strval', (array)$expected), true),
                'contains' => is_array($actual)
                    ? in_array((string)$expected, array_map('strval', $actual), true)
                    : str_contains((string)$actual, (string)$expected),
                'empty' => empty($actual),
                'not_empty' => !empty($actual),
                default => false,
            };
            if (!$ok) return false;
        }
        return true;
    }

    private function valueByPath(array $data, string $path): mixed
    {
        if ($path === '') return null;
        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }
}
