<?php

declare(strict_types=1);



namespace CodeIgniter\I18n;

use CodeIgniter\I18n\Exceptions\I18nException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlCalendar;
use IntlDateFormatter;
use Locale;


trait TimeTrait
{
    
    protected $timezone;

    
    protected $locale;

    
    protected $toStringFormat = 'yyyy-MM-dd HH:mm:ss';

    
    protected static $relativePattern = '/this|next|last|tomorrow|yesterday|midnight|today|[+-]|first|last|ago/i';

    
    protected static $testNow;

    
    
    

    
    public function __construct(?string $time = null, $timezone = null, ?string $locale = null)
    {
        $this->locale = in_array($locale, [null, '', '0'], true) ? Locale::getDefault() : $locale;

        $time ??= '';

        
        if ($time === '' && static::$testNow instanceof static) {
            if ($timezone !== null) {
                $testNow = static::$testNow->setTimezone($timezone);
                $time    = $testNow->format('Y-m-d H:i:s.u');
            } else {
                $timezone = static::$testNow->getTimezone();
                $time     = static::$testNow->format('Y-m-d H:i:s.u');
            }
        }

        $timezone       = $timezone ?: date_default_timezone_get();
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);

        
        
        
        if ($time !== '' && static::hasRelativeKeywords($time)) {
            $instance = new DateTime('now', $this->timezone);
            $instance->modify($time);
            $time = $instance->format('Y-m-d H:i:s.u');
        }

        parent::__construct($time, $this->timezone);
    }

    
    public static function now($timezone = null, ?string $locale = null)
    {
        return new static(null, $timezone, $locale);
    }

    
    public static function parse(string $datetime, $timezone = null, ?string $locale = null)
    {
        return new static($datetime, $timezone, $locale);
    }

    
    public static function today($timezone = null, ?string $locale = null)
    {
        return new static(date('Y-m-d 00:00:00'), $timezone, $locale);
    }

    
    public static function yesterday($timezone = null, ?string $locale = null)
    {
        return new static(date('Y-m-d 00:00:00', strtotime('-1 day')), $timezone, $locale);
    }

    
    public static function tomorrow($timezone = null, ?string $locale = null)
    {
        return new static(date('Y-m-d 00:00:00', strtotime('+1 day')), $timezone, $locale);
    }

    
    public static function createFromDate(?int $year = null, ?int $month = null, ?int $day = null, $timezone = null, ?string $locale = null)
    {
        return static::create($year, $month, $day, null, null, null, $timezone, $locale);
    }

    
    public static function createFromTime(?int $hour = null, ?int $minutes = null, ?int $seconds = null, $timezone = null, ?string $locale = null)
    {
        return static::create(null, null, null, $hour, $minutes, $seconds, $timezone, $locale);
    }

    
    public static function create(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        ?int $hour = null,
        ?int $minutes = null,
        ?int $seconds = null,
        $timezone = null,
        ?string $locale = null,
    ) {
        $year ??= date('Y');
        $month ??= date('m');
        $day ??= date('d');
        $hour ??= 0;
        $minutes ??= 0;
        $seconds ??= 0;

        return new static(date('Y-m-d H:i:s', strtotime("{$year}-{$month}-{$day} {$hour}:{$minutes}:{$seconds}")), $timezone, $locale);
    }

    
    public static function createFromFormat($format, $datetime, $timezone = null): static
    {
        if (! $date = parent::createFromFormat($format, $datetime)) {
            throw I18nException::forInvalidFormat($format);
        }

        return new static($date->format('Y-m-d H:i:s.u'), $timezone);
    }

    
    public static function createFromTimestamp(float|int $timestamp, $timezone = null, ?string $locale = null): static
    {
        $time = new static(sprintf('@%.6f', $timestamp), 'UTC', $locale);

        $timezone ??= 'UTC';

        return $time->setTimezone($timezone);
    }

    
    public static function createFromInstance(DateTimeInterface $dateTime, ?string $locale = null)
    {
        $date     = $dateTime->format('Y-m-d H:i:s.u');
        $timezone = $dateTime->getTimezone();

        return new static($date, $timezone, $locale);
    }

    
    public static function instance(DateTime $dateTime, ?string $locale = null)
    {
        return static::createFromInstance($dateTime, $locale);
    }

    
    public function toDateTime()
    {
        return DateTime::createFromFormat(
            'Y-m-d H:i:s.u',
            $this->format('Y-m-d H:i:s.u'),
            $this->getTimezone(),
        );
    }

    
    
    

    
    public static function setTestNow($datetime = null, $timezone = null, ?string $locale = null)
    {
        
        if ($datetime === null) {
            static::$testNow = null;

            return;
        }

        
        if (is_string($datetime)) {
            $datetime = new static($datetime, $timezone, $locale);
        } elseif ($datetime instanceof DateTimeInterface && ! $datetime instanceof static) {
            $datetime = new static($datetime->format('Y-m-d H:i:s.u'), $timezone);
        }

        static::$testNow = $datetime;
    }

    
    public static function hasTestNow(): bool
    {
        return static::$testNow !== null;
    }

    
    
    

    
    public function getYear(): string
    {
        return $this->toLocalizedString('y');
    }

    
    public function getMonth(): string
    {
        return $this->toLocalizedString('M');
    }

    
    public function getDay(): string
    {
        return $this->toLocalizedString('d');
    }

    
    public function getHour(): string
    {
        return $this->toLocalizedString('H');
    }

    
    public function getMinute(): string
    {
        return $this->toLocalizedString('m');
    }

    
    public function getSecond(): string
    {
        return $this->toLocalizedString('s');
    }

    
    public function getDayOfWeek(): string
    {
        return $this->toLocalizedString('c');
    }

    
    public function getDayOfYear(): string
    {
        return $this->toLocalizedString('D');
    }

    
    public function getWeekOfMonth(): string
    {
        return $this->toLocalizedString('W');
    }

    
    public function getWeekOfYear(): string
    {
        return $this->toLocalizedString('w');
    }

    
    public function getAge()
    {
        
        return max(0, $this->difference(static::now())->getYears());
    }

    
    public function getQuarter(): string
    {
        return $this->toLocalizedString('Q');
    }

    
    public function getDst(): bool
    {
        return $this->format('I') === '1'; 
    }

    
    public function getLocal(): bool
    {
        $local = date_default_timezone_get();

        return $local === $this->timezone->getName();
    }

    
    public function getUtc(): bool
    {
        return $this->getOffset() === 0;
    }

    
    public function getTimezoneName(): string
    {
        return $this->timezone->getName();
    }

    
    
    

    
    public function setYear($value)
    {
        return $this->setValue('year', $value);
    }

    
    public function setMonth($value)
    {
        if (is_numeric($value) && ($value < 1 || $value > 12)) {
            throw I18nException::forInvalidMonth((string) $value);
        }

        if (is_string($value) && ! is_numeric($value)) {
            $value = date('m', strtotime("{$value} 1 2017"));
        }

        return $this->setValue('month', $value);
    }

    
    public function setDay($value)
    {
        if ($value < 1 || $value > 31) {
            throw I18nException::forInvalidDay((string) $value);
        }

        $date    = $this->getYear() . '-' . $this->getMonth();
        $lastDay = date('t', strtotime($date));
        if ($value > $lastDay) {
            throw I18nException::forInvalidOverDay($lastDay, (string) $value);
        }

        return $this->setValue('day', $value);
    }

    
    public function setHour($value)
    {
        if ($value < 0 || $value > 23) {
            throw I18nException::forInvalidHour((string) $value);
        }

        return $this->setValue('hour', $value);
    }

    
    public function setMinute($value)
    {
        if ($value < 0 || $value > 59) {
            throw I18nException::forInvalidMinutes((string) $value);
        }

        return $this->setValue('minute', $value);
    }

    
    public function setSecond($value)
    {
        if ($value < 0 || $value > 59) {
            throw I18nException::forInvalidSeconds((string) $value);
        }

        return $this->setValue('second', $value);
    }

    
    protected function setValue(string $name, $value)
    {
        [$year, $month, $day, $hour, $minute, $second] = explode('-', $this->format('Y-n-j-G-i-s'));

        ${$name} = $value;

        return static::create(
            (int) $year,
            (int) $month,
            (int) $day,
            (int) $hour,
            (int) $minute,
            (int) $second,
            $this->getTimezoneName(),
            $this->locale,
        );
    }

    
    public function setTimezone($timezone): static
    {
        $timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
        $dateTime = $this->toDateTime()->setTimezone($timezone);

        return static::createFromInstance($dateTime, $this->locale);
    }

    
    
    

    
    public function addSeconds(int $seconds)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$seconds} seconds"));
    }

    
    public function addMinutes(int $minutes)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$minutes} minutes"));
    }

    
    public function addHours(int $hours)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$hours} hours"));
    }

    
    public function addDays(int $days)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$days} days"));
    }

    
    public function addMonths(int $months)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$months} months"));
    }

    
    public function addCalendarMonths(int $months): static
    {
        $time = clone $this;

        $year  = (int) $time->getYear();
        $month = (int) $time->getMonth();
        $day   = (int) $time->getDay();

        
        $totalMonths = ($year * 12 + $month - 1) + $months;

        
        $newYear  = intdiv($totalMonths, 12);
        $newMonth = $totalMonths % 12 + 1;

        
        $lastDayOfMonth = cal_days_in_month(CAL_GREGORIAN, $newMonth, $newYear);
        $correctedDay   = min($day, $lastDayOfMonth);

        return static::create($newYear, $newMonth, $correctedDay, (int) $this->getHour(), (int) $this->getMinute(), (int) $this->getSecond(), $this->getTimezone(), $this->locale);
    }

    
    public function subCalendarMonths(int $months): static
    {
        return $this->addCalendarMonths(-$months);
    }

    
    public function addYears(int $years)
    {
        $time = clone $this;

        return $time->add(DateInterval::createFromDateString("{$years} years"));
    }

    
    public function subSeconds(int $seconds)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$seconds} seconds"));
    }

    
    public function subMinutes(int $minutes)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$minutes} minutes"));
    }

    
    public function subHours(int $hours)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$hours} hours"));
    }

    
    public function subDays(int $days)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$days} days"));
    }

    
    public function subMonths(int $months)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$months} months"));
    }

    
    public function subYears(int $years)
    {
        $time = clone $this;

        return $time->sub(DateInterval::createFromDateString("{$years} years"));
    }

    
    
    

    
    public function toDateTimeString()
    {
        return $this->toLocalizedString('yyyy-MM-dd HH:mm:ss');
    }

    
    public function toDateString()
    {
        return $this->toLocalizedString('yyyy-MM-dd');
    }

    
    public function toFormattedDateString()
    {
        return $this->toLocalizedString('MMM d, yyyy');
    }

    
    public function toTimeString()
    {
        return $this->toLocalizedString('HH:mm:ss');
    }

    
    public function toLocalizedString(?string $format = null)
    {
        $format ??= $this->toStringFormat;

        return IntlDateFormatter::formatObject($this->toDateTime(), $format, $this->locale);
    }

    
    
    

    
    public function equals($testTime, ?string $timezone = null): bool
    {
        $testTime = $this->getUTCObject($testTime, $timezone);

        $ourTime = $this->toDateTime()
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.u');

        return $testTime->format('Y-m-d H:i:s.u') === $ourTime;
    }

    
    public function sameAs($testTime, ?string $timezone = null): bool
    {
        if ($testTime instanceof DateTimeInterface) {
            $testTime = $testTime->format('Y-m-d H:i:s.u O');
        } elseif (is_string($testTime)) {
            $timezone = in_array($timezone, [null, '', '0'], true) ? $this->timezone : $timezone;
            $timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
            $testTime = new DateTime($testTime, $timezone);
            $testTime = $testTime->format('Y-m-d H:i:s.u O');
        }

        $ourTime = $this->format('Y-m-d H:i:s.u O');

        return $testTime === $ourTime;
    }

    
    public function isBefore($testTime, ?string $timezone = null): bool
    {
        $testTime = $this->getUTCObject($testTime, $timezone);

        $testTimestamp = $testTime->getTimestamp();
        $ourTimestamp  = $this->getTimestamp();

        if ($ourTimestamp === $testTimestamp) {
            return $this->format('u') < $testTime->format('u');
        }

        return $ourTimestamp < $testTimestamp;
    }

    
    public function isAfter($testTime, ?string $timezone = null): bool
    {
        $testTime = $this->getUTCObject($testTime, $timezone);

        $testTimestamp = $testTime->getTimestamp();
        $ourTimestamp  = $this->getTimestamp();

        if ($ourTimestamp === $testTimestamp) {
            return $this->format('u') > $testTime->format('u');
        }

        return $ourTimestamp > $testTimestamp;
    }

    
    public function isPast(): bool
    {
        return $this->isBefore(static::now($this->timezone));
    }

    
    public function isFuture(): bool
    {
        return $this->isAfter(static::now($this->timezone));
    }

    
    
    

    
    public function humanize()
    {
        $now  = IntlCalendar::fromDateTime(self::now($this->timezone)->toDateTime());
        $time = $this->getCalendar()->getTime();

        $years   = $now->fieldDifference($time, IntlCalendar::FIELD_YEAR);
        $months  = $now->fieldDifference($time, IntlCalendar::FIELD_MONTH);
        $days    = $now->fieldDifference($time, IntlCalendar::FIELD_DAY_OF_YEAR);
        $hours   = $now->fieldDifference($time, IntlCalendar::FIELD_HOUR_OF_DAY);
        $minutes = $now->fieldDifference($time, IntlCalendar::FIELD_MINUTE);

        $phrase = null;

        if ($years !== 0) {
            $phrase = lang('Time.years', [abs($years)]);
            $before = $years < 0;
        } elseif ($months !== 0) {
            $phrase = lang('Time.months', [abs($months)]);
            $before = $months < 0;
        } elseif ($days !== 0 && (abs($days) >= 7)) {
            $weeks  = ceil($days / 7);
            $phrase = lang('Time.weeks', [abs($weeks)]);
            $before = $days < 0;
        } elseif ($days !== 0) {
            $before = $days < 0;

            
            if (abs($days) === 1) {
                return $before ? lang('Time.yesterday') : lang('Time.tomorrow');
            }

            $phrase = lang('Time.days', [abs($days)]);
        } elseif ($hours !== 0) {
            $phrase = lang('Time.hours', [abs($hours)]);
            $before = $hours < 0;
        } elseif ($minutes !== 0) {
            $phrase = lang('Time.minutes', [abs($minutes)]);
            $before = $minutes < 0;
        } else {
            return lang('Time.now');
        }

        return $before ? lang('Time.ago', [$phrase]) : lang('Time.inFuture', [$phrase]);
    }

    
    public function difference($testTime, ?string $timezone = null)
    {
        if (is_string($testTime)) {
            $timezone = ($timezone !== null) ? new DateTimeZone($timezone) : $this->timezone;
            $testTime = new DateTime($testTime, $timezone);
        } elseif ($testTime instanceof static) {
            $testTime = $testTime->toDateTime();
        }

        assert($testTime instanceof DateTime);

        if ($this->timezone->getOffset($this) !== $testTime->getTimezone()->getOffset($this)) {
            $testTime = $this->getUTCObject($testTime, $timezone);
            $ourTime  = $this->getUTCObject($this);
        } else {
            $ourTime = $this->toDateTime();
        }

        return new TimeDifference($ourTime, $testTime);
    }

    
    
    

    
    public function getUTCObject($time, ?string $timezone = null)
    {
        if ($time instanceof static) {
            $time = $time->toDateTime();
        } elseif (is_string($time)) {
            $timezone = in_array($timezone, [null, '', '0'], true) ? $this->timezone : $timezone;
            $timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
            $time     = new DateTime($time, $timezone);
        }

        if ($time instanceof DateTime || $time instanceof DateTimeImmutable) {
            $time = $time->setTimezone(new DateTimeZone('UTC'));
        }

        return $time;
    }

    
    public function getCalendar()
    {
        return IntlCalendar::fromDateTime($this->toDateTime());
    }

    
    protected static function hasRelativeKeywords(string $time): bool
    {
        
        if (preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $time) !== 1) {
            return preg_match(static::$relativePattern, $time) > 0;
        }

        return false;
    }

    
    public function __toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return null;
    }

    
    public function __isset($name): bool
    {
        $method = 'get' . ucfirst($name);

        return method_exists($this, $method);
    }

    
    public function __unserialize(array $data): void
    {
        parent::__construct($data['date'], new DateTimeZone($data['timezone']));
    }
}
