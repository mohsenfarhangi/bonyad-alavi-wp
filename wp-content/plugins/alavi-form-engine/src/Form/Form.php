<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Form;

final class Form
{
    private string $title = '';
    private string $description = '';
    private array $steps = [];
    private array $settings = [];
    private array $workflow = [];
    private array $actions = [];
    private string $storage = 'shared';
    private ?string $template = null;

    public function __construct(private readonly string $slug) {}

    public static function make(string $slug): self { return new self($slug); }
    public function title(string $title): self { $this->title = $title; return $this; }
    public function description(string $description): self { $this->description = $description; return $this; }
    public function steps(array $steps): self { $this->steps = $steps; return $this; }
    public function settings(array $settings): self { $this->settings = array_replace_recursive($this->settings, $settings); return $this; }
    public function styleIsolation(string $mode): self { $this->settings['style_isolation'] = $mode; return $this; }
    public function preview(bool $enabled = true): self { $this->settings['preview_enabled'] = $enabled; return $this; }
    public function previewTemplate(string $html): self { $this->settings['preview_template'] = $html; return $this; }
    public function lockAfterSubmit(bool $enabled = true): self { $this->settings['lock_after_submit'] = $enabled; return $this; }
    public function showEditRequestButton(bool $show = true): self { $this->settings['show_edit_request_button'] = $show; return $this; }
    public function workflow(array $workflow): self { $this->workflow = $workflow; return $this; }
    public function actions(array $actions): self { $this->actions = $actions; return $this; }
    public function storage(string $storage): self { $this->storage = $storage; return $this; }
    public function template(string $template): self { $this->template = $template; return $this; }

    public function slug(): string { return $this->slug; }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'steps' => array_map(static fn(Step $step) => $step->toArray(), $this->steps),
            'settings' => array_replace_recursive([
                'wizard' => true,
                'save_draft' => true,
                'show_progress' => true,
                'editing_enabled' => true,
                'editing_modes' => ['wordpress', 'link', 'tracking'],
                'preview_enabled' => false,
                'preview_title' => 'پیش‌نمایش اطلاعات ارسالی',
                'preview_description' => 'اطلاعات زیر را با دقت بررسی کنید.',
                'preview_template' => '',
                'lock_after_submit' => false,
                'show_edit_request_button' => false,
                'lock_warning' => 'پس از ثبت نهایی، امکان ویرایش اطلاعات وجود نخواهد داشت مگر اینکه درخواست ویرایش شما توسط مدیر تأیید شود.',
                'captcha' => 'custom',
                'rate_limit' => 10,
            ], $this->settings),
            'workflow' => $this->workflow ?: [
                'draft' => 'پیش‌نویس',
                'new' => 'جدید',
                'review' => 'در حال بررسی',
                'revision' => 'نیاز به اصلاح',
                'approved' => 'تأیید شده',
                'rejected' => 'رد شده',
            ],
            'actions' => $this->actions,
            'storage' => $this->storage,
            'template' => $this->template,
        ];
    }
}
