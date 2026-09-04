<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Validation;

use BonyadAlavi\FormEngine\Security\Validators;
use Closure;
use InvalidArgumentException;

final class ValidatorRegistry
{
    public const CUSTOM_REGEX_MAX_LENGTH = 500;
    public const CUSTOM_REGEX_FLAGS = 'imsux';

    /** @var array<string,ValidatorDefinition> */
    private array $definitions = [];

    public function __construct()
    {
        $textual = ['text','textarea','tel','email','url'];
        $identifier = ['text','tel'];
        $this->register(new ValidatorDefinition('national_id','کد ملی ایران',$identifier,'کد ملی واردشده معتبر نیست.',Closure::fromCallable([Validators::class,'nationalId'])));
        $this->register(new ValidatorDefinition('mobile','شماره موبایل ایران',['text','tel'],'شماره همراه معتبر نیست.',Closure::fromCallable([Validators::class,'mobile'])));
        $this->register(new ValidatorDefinition('iban','شبا ایران',$identifier,'شماره شبا معتبر نیست.',Closure::fromCallable([Validators::class,'iranIbanFlexible'])));
        $this->register(new ValidatorDefinition('email','Email',['text','email'],'نشانی ایمیل معتبر نیست.',static fn(string $value): bool => filter_var($value, FILTER_VALIDATE_EMAIL) !== false));
        $this->register(new ValidatorDefinition('url','URL',['text','url'],'نشانی وب معتبر نیست.',static fn(string $value): bool => filter_var($value, FILTER_VALIDATE_URL) !== false));
        $this->register(new ValidatorDefinition('postal_code','کد پستی ۱۰ رقمی ایران',$identifier,'کد پستی باید دقیقاً ۱۰ رقم باشد.',Closure::fromCallable([Validators::class,'iranPostalCode'])));
        $this->register(new ValidatorDefinition('bank_card','شماره کارت بانکی ۱۶ رقمی با checksum',$identifier,'شماره کارت بانکی معتبر نیست.',Closure::fromCallable([Validators::class,'iranBankCard'])));
        $this->register(new ValidatorDefinition('landline','تلفن ثابت ایران',$identifier,'شماره تلفن ثابت معتبر نیست.',Closure::fromCallable([Validators::class,'iranLandline'])));
        $this->register(new ValidatorDefinition('date','اعتبارسنجی تاریخ',['date','text'],'تاریخ واردشده معتبر نیست.',static function(string $value,array $field): bool {
            return (($field['calendar']??'gregorian') === 'jalali') ? Validators::jalaliDate($value) : Validators::gregorianDate($value);
        }));
        $this->register(new ValidatorDefinition('custom_regex','Custom Regex',$textual,'مقدار واردشده با الگوی تعریف‌شده مطابقت ندارد.',static function(string $value,array $field,array $config): bool {
            $pattern=(string)($config['pattern']??'');
            $flags=(string)($config['flags']??'u');
            $compiled=self::compileRegex($pattern,$flags);
            return $compiled !== null && @preg_match($compiled,$value) === 1;
        }));
    }

    public function register(ValidatorDefinition $definition): void
    {
        $key=preg_replace('/[^a-z0-9_\-]/','',strtolower($definition->key)) ?? '';
        if ($key==='' || $key!==$definition->key) throw new InvalidArgumentException('Validator key must be a lowercase slug.');
        $this->definitions[$key]=$definition;
    }

    public function get(string $key): ?ValidatorDefinition { return $this->definitions[$key]??null; }
    /** @return array<string,ValidatorDefinition> */
    public function all(): array { return $this->definitions; }
    /** @return array<string,ValidatorDefinition> */
    public function forFieldType(string $type): array
    {
        return array_filter($this->definitions,static fn(ValidatorDefinition $definition): bool => $definition->supports($type));
    }

    public static function sanitizeRegexFlags(string $flags): string
    {
        $out='';
        foreach (str_split(strtolower($flags)) as $flag) {
            if (str_contains(self::CUSTOM_REGEX_FLAGS,$flag) && !str_contains($out,$flag)) $out.=$flag;
        }
        if (!str_contains($out,'u')) $out.='u';
        return $out;
    }

    public static function compileRegex(string $pattern,string $flags='u'): ?string
    {
        $pattern=trim($pattern);
        if ($pattern==='' || strlen($pattern)>self::CUSTOM_REGEX_MAX_LENGTH || str_contains($pattern,"\0")) return null;
        $flags=self::sanitizeRegexFlags($flags);
        $compiled='~(*LIMIT_MATCH=100000)(*LIMIT_RECURSION=10000)(?:'.str_replace('~','\\~',$pattern).')~'.$flags;
        return @preg_match($compiled,'') === false ? null : $compiled;
    }
}
