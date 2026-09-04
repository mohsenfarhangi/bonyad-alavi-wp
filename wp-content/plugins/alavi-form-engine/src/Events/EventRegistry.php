<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Events;

final class EventRegistry
{
    /** @var array<string, EventDefinition> */
    private array $events = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function register(EventDefinition $event): void
    {
        $this->events[$event->key] = $event;
    }

    public function has(string $key): bool
    {
        return isset($this->events[$key]);
    }

    public function get(string $key): ?EventDefinition
    {
        return $this->events[$key] ?? null;
    }

    /** @return array<string, EventDefinition> */
    public function all(): array
    {
        return $this->events;
    }

    /** @return array<string, string> */
    public function labels(): array
    {
        $labels = [];
        foreach ($this->events as $key => $event) {
            $labels[$key] = $event->label;
        }
        return $labels;
    }

    private function registerDefaults(): void
    {
        foreach ([
            new EventDefinition('submission.created', 'ایجاد ثبت جدید', 'پس از ایجاد اولین رکورد برای فرم.'),
            new EventDefinition('submission.draft_saved', 'ذخیره پیش‌نویس', 'پس از ذخیره موفق پیش‌نویس.'),
            new EventDefinition('submission.submitted', 'ثبت نهایی فرم', 'پس از ثبت نهایی موفق فرم.'),
            new EventDefinition('submission.updated', 'ویرایش اطلاعات فرم', 'پس از ذخیره موفق تغییرات یک ثبت موجود.'),
            new EventDefinition('submission.status_changed', 'تغییر وضعیت ثبت', 'پس از تغییر Workflow/Status ثبت.'),
            new EventDefinition('submission.locked', 'قفل شدن فرم', 'پس از قفل شدن ثبت برای ویرایش.'),
            new EventDefinition('submission.unlocked', 'باز شدن قفل فرم', 'پس از باز شدن قفل ثبت.'),
            new EventDefinition('edit_request.created', 'ثبت درخواست ویرایش', 'پس از ارسال درخواست ویرایش توسط متقاضی.', 'edit_request'),
            new EventDefinition('edit_request.approved', 'تأیید درخواست ویرایش', 'پس از تأیید درخواست و باز شدن قفل.', 'edit_request'),
            new EventDefinition('edit_request.rejected', 'رد درخواست ویرایش', 'پس از رد درخواست ویرایش.', 'edit_request'),
            new EventDefinition('submission.trashed', 'انتقال به زباله‌دان', 'پس از انتقال ثبت به زباله‌دان.', 'lifecycle'),
            new EventDefinition('submission.restored', 'بازیابی از زباله‌دان', 'پس از بازیابی ثبت.', 'lifecycle'),
        ] as $event) {
            $this->register($event);
        }

        /** @var iterable<EventDefinition> $custom */
        $custom = apply_filters('afe_event_definitions', []);
        foreach ($custom as $event) {
            if ($event instanceof EventDefinition) {
                $this->register($event);
            }
        }
    }
}
