<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Localization;

use DateTimeImmutable;
use DateTimeZone;

/**
 * WordPress-locale aware date boundary/format service.
 *
 * WordPress localizes Gregorian date strings but does not switch calendars for
 * fa_* locales. AFE treats Persian site locales as Jalali for admin display and
 * filter inputs, while keeping UTC Gregorian timestamps in the database.
 */
final class LocaleDateService
{
    public function locale(): string
    {
        $locale = function_exists('get_locale') ? (string)get_locale() : 'en_US';
        return (string)apply_filters('afe_date_locale', $locale);
    }

    public function isJalali(): bool
    {
        return str_starts_with(strtolower($this->locale()), 'fa');
    }

    public function calendar(): string
    {
        return $this->isJalali() ? 'jalali' : 'gregorian';
    }

    /** Format a UTC MySQL timestamp in the WordPress timezone/calendar. */
    public function formatUtc(?string $utcMysql, bool $withTime = true): string
    {
        $utcMysql = trim((string)$utcMysql);
        if ($utcMysql === '') return '—';

        try {
            $utc = new DateTimeImmutable($utcMysql, new DateTimeZone('UTC'));
            $local = $utc->setTimezone(wp_timezone());
        } catch (\Throwable) {
            return $utcMysql;
        }

        if (!$this->isJalali()) {
            $format = (string)get_option('date_format', 'Y-m-d');
            if ($withTime) $format .= ' ' . (string)get_option('time_format', 'H:i');
            return wp_date($format, $local->getTimestamp(), wp_timezone());
        }

        [$jy,$jm,$jd] = $this->gregorianToJalali(
            (int)$local->format('Y'),
            (int)$local->format('n'),
            (int)$local->format('j')
        );
        $date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        return $withTime ? $date . ' ' . $local->format('H:i') : $date;
    }

    /**
     * Format a form date value according to the site calendar.
     * $sourceCalendar describes how the field is stored, not how it is shown.
     */
    public function formatFormDate(mixed $value, string $sourceCalendar = 'gregorian'): string
    {
        $raw = $this->normalizeDigits(trim((string)$value));
        if ($raw === '') return '—';
        $parts = $this->dateParts($raw);
        if (!$parts) return (string)$value;

        $sourceCalendar = $sourceCalendar === 'jalali' ? 'jalali' : 'gregorian';
        [$y,$m,$d] = $parts;

        if ($this->isJalali()) {
            if ($sourceCalendar === 'gregorian') [$y,$m,$d] = $this->gregorianToJalali($y,$m,$d);
            return sprintf('%04d/%02d/%02d',$y,$m,$d);
        }

        if ($sourceCalendar === 'jalali') [$y,$m,$d] = $this->jalaliToGregorian($y,$m,$d);
        try {
            $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d',$y,$m,$d), wp_timezone());
            return wp_date((string)get_option('date_format','Y-m-d'), $date->getTimestamp(), wp_timezone());
        } catch (\Throwable) {
            return sprintf('%04d-%02d-%02d',$y,$m,$d);
        }
    }

    /** Value used in an admin editable date field (site calendar). */
    public function editValue(mixed $value, string $sourceCalendar = 'gregorian'): string
    {
        $raw=$this->normalizeDigits(trim((string)$value));
        if($raw==='') return '';
        $parts=$this->dateParts($raw);
        if(!$parts) return $raw;
        [$y,$m,$d]=$parts;
        $sourceCalendar=$sourceCalendar==='jalali'?'jalali':'gregorian';
        if($this->isJalali()){
            if($sourceCalendar==='gregorian') [$y,$m,$d]=$this->gregorianToJalali($y,$m,$d);
            return sprintf('%04d/%02d/%02d',$y,$m,$d);
        }
        if($sourceCalendar==='jalali') [$y,$m,$d]=$this->jalaliToGregorian($y,$m,$d);
        return sprintf('%04d-%02d-%02d',$y,$m,$d);
    }

    /** Convert a site-calendar edited value back to the field's storage calendar. */
    public function normalizeEditedFormDate(mixed $value, string $targetCalendar = 'gregorian'): string
    {
        $raw=$this->normalizeDigits(trim((string)$value));
        if ($raw==='') return '';
        $parts=$this->dateParts($raw);
        if(!$parts) return sanitize_text_field($raw);
        [$y,$m,$d]=$parts;
        $targetCalendar=$targetCalendar==='jalali'?'jalali':'gregorian';

        if($this->isJalali() && $targetCalendar==='gregorian') [$y,$m,$d]=$this->jalaliToGregorian($y,$m,$d);
        elseif(!$this->isJalali() && $targetCalendar==='jalali') [$y,$m,$d]=$this->gregorianToJalali($y,$m,$d);

        return $targetCalendar==='jalali'
            ? sprintf('%04d/%02d/%02d',$y,$m,$d)
            : sprintf('%04d-%02d-%02d',$y,$m,$d);
    }

    /**
     * Convert a date entered in the site's admin calendar to a UTC SQL boundary.
     */
    public function filterBoundary(string $input, bool $endOfDay = false): ?string
    {
        $input=$this->normalizeDigits(trim($input));
        if($input==='') return null;
        $parts=$this->dateParts($input);
        if(!$parts) return null;
        [$y,$m,$d]=$parts;
        if($this->isJalali()){
            if(!$this->isValidJalali($y,$m,$d)) return null;
            [$y,$m,$d]=$this->jalaliToGregorian($y,$m,$d);
        }

        if(!checkdate($m,$d,$y)) return null;
        try {
            $local=(new DateTimeImmutable(sprintf('%04d-%02d-%02d',$y,$m,$d),wp_timezone()))
                ->setTime($endOfDay?23:0,$endOfDay?59:0,$endOfDay?59:0);
            return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    public function filterPlaceholder(): string
    {
        return $this->isJalali() ? '1405/06/01' : 'YYYY-MM-DD';
    }

    private function toIsoGregorian(string $display): string
    {
        $parts=$this->dateParts($display);
        if(!$parts) return $display;
        return sprintf('%04d-%02d-%02d',$parts[0],$parts[1],$parts[2]);
    }

    /** @return array{0:int,1:int,2:int}|null */
    private function dateParts(string $value): ?array
    {
        if(!preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/u',$value,$m)) return null;
        return [(int)$m[1],(int)$m[2],(int)$m[3]];
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value,[
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    private function isValidJalali(int $jy,int $jm,int $jd): bool
    {
        if($jm<1||$jm>12||$jd<1||$jd>31) return false;
        if($jm>6 && $jd>30) return false;
        [$gy,$gm,$gd]=$this->jalaliToGregorian($jy,$jm,$jd);
        [$ry,$rm,$rd]=$this->gregorianToJalali($gy,$gm,$gd);
        return $jy===$ry && $jm===$rm && $jd===$rd;
    }

    /** @return array{0:int,1:int,2:int} */
    public function gregorianToJalali(int $gy,int $gm,int $gd): array
    {
        $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
        if($gy>1600){$jy=979;$gy-=1600;}else{$jy=0;$gy-=621;}
        $gy2=$gm>2?$gy+1:$gy;
        $days=365*$gy+intdiv($gy2+3,4)-intdiv($gy2+99,100)+intdiv($gy2+399,400)-80+$gd+$gdm[$gm-1];
        $jy+=33*intdiv($days,12053); $days%=12053;
        $jy+=4*intdiv($days,1461); $days%=1461;
        if($days>365){$jy+=intdiv($days-1,365);$days=($days-1)%365;}
        if($days<186){$jm=1+intdiv($days,31);$jd=1+($days%31);}else{$jm=7+intdiv($days-186,30);$jd=1+(($days-186)%30);}
        return [$jy,$jm,$jd];
    }

    /** @return array{0:int,1:int,2:int} */
    public function jalaliToGregorian(int $jy,int $jm,int $jd): array
    {
        if($jy>979){$gy=1600;$jy-=979;}else{$gy=621;}
        $days=365*$jy+(intdiv($jy,33)*8)+intdiv(($jy%33)+3,4)+78+$jd+($jm<7?($jm-1)*31:(($jm-7)*30)+186);
        $gy+=400*intdiv($days,146097); $days%=146097;
        if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}
        $gy+=4*intdiv($days,1461); $days%=1461;
        if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}
        $gd=$days+1;
        $leap=($gy%4===0&&$gy%100!==0)||($gy%400===0);
        $months=[0,31,$leap?29:28,31,30,31,30,31,31,30,31,30,31];
        for($gm=1;$gm<=12 && $gd>$months[$gm];$gm++) $gd-=$months[$gm];
        return [$gy,$gm,$gd];
    }
}
