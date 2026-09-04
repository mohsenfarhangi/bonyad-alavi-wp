<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form\Fields;

abstract class AbstractField
{
    protected string $name;
    protected string $type;
    protected array $config = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->type = static::TYPE;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static { $this->config['label'] = $label; return $this; }
    public function description(string $description): static { $this->config['description'] = $description; return $this; }
    public function placeholder(string $placeholder): static { $this->config['placeholder'] = $placeholder; return $this; }
    public function required(bool $required = true): static { $this->config['required'] = $required; return $this; }
    public function default(mixed $value): static { $this->config['default'] = $value; return $this; }
    public function options(array $options): static { $this->config['options'] = $options; return $this; }
    public function source(array|string $source): static { $this->config['source'] = $source; return $this; }
    public function dependsOn(string $field): static { $this->config['depends_on'] = $field; return $this; }
    public function rules(array $rules): static { $this->config['rules'] = $rules; return $this; }
    public function rule(string $rule, mixed $value = true): static { $this->config['rules'][$rule] = $value; return $this; }
    public function attributes(array $attributes): static { $this->config['attributes'] = $attributes; return $this; }
    public function width(int $columns): static { $this->config['width'] = max(1, min(12, $columns)); return $this; }
    public function multiple(bool $multiple = true): static { $this->config['multiple'] = $multiple; return $this; }
    public function maxFiles(int $count): static { $this->config['max_files'] = max(1, $count); return $this; }
    public function maxSizeMb(int $mb): static { $this->config['max_size_mb'] = max(1, $mb); return $this; }
    public function accept(array $mimes): static { $this->config['accept'] = $mimes; return $this; }
    public function condition(array $conditions, string $effect = 'show'): static
    {
        $this->config['conditions'][] = ['when' => $conditions, 'effect' => $effect];
        return $this;
    }
    public function uniqueGroup(string $group): static { $this->config['unique_group'] = $group; return $this; }
    public function inputMask(string $key): static { $this->config['input_mask'] = ['key' => strtolower(trim($key))]; return $this; }
    public function customInputMask(string $pattern): static { $this->config['input_mask'] = ['key' => 'custom', 'pattern' => trim($pattern)]; return $this; }
    public function meta(string $key, mixed $value): static { $this->config[$key] = $value; return $this; }

    public function name(): string { return $this->name; }
    public function type(): string { return $this->type; }
    public function config(): array { return $this->config; }

    public function toArray(): array
    {
        return array_merge([
            'name' => $this->name,
            'type' => $this->type,
            'required' => false,
            'width' => 12,
        ], $this->config);
    }
}
