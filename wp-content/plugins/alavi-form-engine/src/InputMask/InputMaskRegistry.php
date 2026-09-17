<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\InputMask;

use InvalidArgumentException;

final class InputMaskRegistry
{
    /** @var array<string,InputMaskDefinition> */
    private array $definitions = [];

    public function __construct()
    {
        $types = ['text','tel'];
        $this->register(new InputMaskDefinition('mobile_ir','شماره موبایل ایران','9999 999 9999',$types,'0912 345 6789','نمایش گروه‌بندی‌شده؛ مقدار ذخیره‌شده بدون فاصله است.','numeric'));
        $this->register(new InputMaskDefinition('landline_ir','تلفن ثابت ایران','999 9999 9999',$types,'021 1234 5678','برای شماره‌های ۱۱ رقمی شامل صفر ابتدای کد شهر.','numeric'));
        $this->register(new InputMaskDefinition('national_id_ir','کد ملی ایران','9999999999',$types,'0012345678','صفر ابتدایی حفظ می‌شود.','numeric'));
        $this->register(new InputMaskDefinition('postal_code_ir','کد پستی ایران','99999-99999',$types,'12345-67890','خط تیره فقط نمایشی است و ذخیره نمی‌شود.','numeric'));
        $this->register(new InputMaskDefinition('bank_card_ir','شماره کارت بانکی','9999 9999 9999 9999',$types,'6037 9912 3456 7890','فاصله‌ها فقط نمایشی هستند.','numeric'));
        $this->register(new InputMaskDefinition('iban_digits_ir','شماره شبا (۲۴ رقم)','999999999999999999999999',$types,'123456789012345678901234','۲۴ رقم شبا بدون IR و بدون فاصله نمایش و ذخیره می‌شود.','numeric'));
    }

    public function register(InputMaskDefinition $definition): void
    {
        $key = preg_replace('/[^a-z0-9_\-]/','',strtolower($definition->key)) ?? '';
        if ($key === '' || $key !== $definition->key) throw new InvalidArgumentException('Input mask key must be a lowercase slug.');
        if (!InputMaskPattern::isValid($definition->pattern)) throw new InvalidArgumentException('Input mask pattern is invalid: '.$definition->key);
        $this->definitions[$key] = $definition;
    }

    public function get(string $key): ?InputMaskDefinition { return $this->definitions[$key] ?? null; }
    /** @return array<string,InputMaskDefinition> */
    public function all(): array { return $this->definitions; }
    /** @return array<string,InputMaskDefinition> */
    public function forFieldType(string $fieldType): array
    {
        return array_filter($this->definitions, static fn(InputMaskDefinition $definition): bool => $definition->supports($fieldType));
    }
}
