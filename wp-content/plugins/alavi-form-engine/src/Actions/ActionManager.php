<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

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
     * @return array<string, string> action_key => error message
     */
    public function run(array $definitions, ActionContext $context): array
    {
        $errors = [];
        $definitions = $this->flattenDefinitions($definitions);

        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                if (!is_string($definition)) continue;
                $definition = ['type'=>$definition];
            }

            if (array_key_exists('enabled', $definition) && empty($definition['enabled'])) {
                continue;
            }

            $type = sanitize_key((string)($definition['type'] ?? ''));
            if ($type === '' || !$this->registry->has($type)) {
                continue;
            }

            $events = $this->definitionEvents($definition);
            if ($events !== [] && !in_array($context->event, $events, true)) {
                continue;
            }

            if (!empty($definition['when']) && !$this->matches((array)$definition['when'], $context->data)) {
                continue;
            }

            $actionKey = $this->actionKey($definition, $type, (int)$index);
            $policy = sanitize_key((string)($definition['execution_policy'] ?? 'always'));
            if (!in_array($policy, ['always','once_per_submission','first_in_cycle'], true)) {
                $policy = 'always';
            }

            $guardScope = null;
            if ($policy === 'once_per_submission') {
                $guardScope = '__submission__';
            } elseif ($policy === 'first_in_cycle') {
                $guardScope = $context->event;
            }

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
                ]
            );

            try {
                $action = $this->registry->handler($type);
                if ($action instanceof ActionInterface) {
                    $action->handle($context, $config);
                } elseif (is_callable($action)) {
                    $action($context, $config);
                } else {
                    continue;
                }

                $this->executions->finish($logId, 'success');
                if ($guardScope !== null) {
                    $this->executions->markOnce($context->submissionId, $guardScope, $actionKey, 'success');
                }
                do_action('afe_action_completed', $type, $actionKey, $context, $config);
            } catch (Throwable $e) {
                $errors[$actionKey] = $e->getMessage();
                $this->executions->finish($logId, 'failed', $e->getMessage());
                if ($guardScope !== null) {
                    $this->executions->markOnce($context->submissionId, $guardScope, $actionKey, 'failed');
                }
                do_action('afe_action_failed', $type, $actionKey, $e, $context, $config);

                if (sanitize_key((string)($definition['on_error'] ?? 'continue')) === 'stop') {
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Supports both the current flat code definition and the planned admin UI
     * structure: [['event'=>'submission.submitted','actions'=>[...]]].
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
        if (!is_string($encoded) || $encoded === '') {
            return 'legacy_' . $type . '_' . $index;
        }
        return substr('legacy_' . $type . '_' . substr(hash('sha256', $encoded), 0, 12), 0, 80);
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
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
