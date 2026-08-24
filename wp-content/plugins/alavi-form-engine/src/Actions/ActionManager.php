<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use Throwable;

final class ActionManager
{
    /** @var array<string, ActionInterface|callable> */
    private array $actions = [];

    public function __construct()
    {
        $this->register('email', new EmailAction());
        $this->register('webhook', new WebhookAction());
    }

    public function register(string $name, ActionInterface|callable $action): void
    {
        $this->actions[$name] = $action;
    }

    public function run(array $definitions, ActionContext $context): array
    {
        $errors = [];
        foreach ($definitions as $definition) {
            $meta=[];
            if (is_string($definition)) {
                $type = $definition; $config = [];
            } else {
                $meta=$definition;
                $type = (string)($definition['type'] ?? '');
                $config = isset($definition['config']) ? (array)$definition['config'] : (array)$definition;
                unset($config['type'],$config['on'],$config['when']);
            }
            if ($type === '' || !isset($this->actions[$type])) continue;

            $on=(array)($meta['on']??[]);
            if($on && !in_array($context->event,$on,true) && !in_array('submitted',$on,true)) continue;
            if(!empty($meta['when']) && !$this->matches((array)$meta['when'],$context->data)) continue;

            try {
                $action = $this->actions[$type];
                if ($action instanceof ActionInterface) $action->handle($context, $config);
                else $action($context, $config);
                do_action('afe_action_completed', $type, $context, $config);
            } catch (Throwable $e) {
                $errors[$type] = $e->getMessage();
                do_action('afe_action_failed', $type, $e, $context, $config);
            }
        }
        return $errors;
    }

    private function matches(array $conditions,array $data): bool
    {
        foreach($conditions as $condition) {
            if(!is_array($condition)) continue;
            $actual=$data[(string)($condition['field']??'')]??null;
            $expected=$condition['value']??null;
            $ok=match($condition['operator']??'=') {
                '=','==' => (string)$actual===(string)$expected,
                '!=' => (string)$actual!==(string)$expected,
                '>' => (float)$actual>(float)$expected,
                '>=' => (float)$actual>=(float)$expected,
                '<' => (float)$actual<(float)$expected,
                '<=' => (float)$actual<=(float)$expected,
                'in' => in_array((string)$actual,array_map('strval',(array)$expected),true),
                'contains' => is_array($actual)?in_array((string)$expected,array_map('strval',$actual),true):str_contains((string)$actual,(string)$expected),
                'empty' => empty($actual),
                'not_empty' => !empty($actual),
                default => false,
            };
            if(!$ok) return false;
        }
        return true;
    }
}
