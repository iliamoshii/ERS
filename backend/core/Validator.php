<?php
/**
 * Validator
 * Fluent input validation for all form fields.
 */

declare(strict_types=1);

namespace Core;

final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ── Fluent rules ──────────────────────────────────────────

    public function required(string $field, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if (trim((string) $value) === '') {
            $this->errors[$field] = "«{$label}» الزامی است.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if (mb_strlen(trim((string) $value)) < $min) {
            $this->errors[$field] = "«{$label}» باید حداقل {$min} کاراکتر باشد.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if (mb_strlen(trim((string) $value)) > $max) {
            $this->errors[$field] = "«{$label}» نباید بیش از {$max} کاراکتر باشد.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'ایمیل'): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "«{$label}» معتبر نیست.";
        }
        return $this;
    }

    public function phone(string $field, string $label = 'شماره تلفن'): static
    {
        $value = $this->data[$field] ?? '';
        // Iranian mobile: 09XXXXXXXXX (11 digits)
        if (!preg_match('/^09[0-9]{9}$/', trim((string) $value))) {
            $this->errors[$field] = "«{$label}» باید یک شماره موبایل ایرانی معتبر باشد (مثال: ۰۹۱۲۳۴۵۶۷۸۹).";
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label): static
    {
        $a = $this->data[$field]      ?? '';
        $b = $this->data[$otherField] ?? '';
        if ($a !== $b) {
            $this->errors[$field] = "«{$label}» با تکرار آن مطابقت ندارد.";
        }
        return $this;
    }

    public function username(string $field, string $label = 'نام کاربری'): static
    {
        $value = $this->data[$field] ?? '';
        // Letters, numbers, underscore, hyphen — 3 to 80 chars
        if (!preg_match('/^[a-zA-Z0-9_\-]{3,80}$/', (string) $value)) {
            $this->errors[$field] = "«{$label}» فقط می‌تواند شامل حروف انگلیسی، اعداد، خط تیره و زیرخط باشد (۳–۸۰ کاراکتر).";
        }
        return $this;
    }

    public function password(string $field, string $label = 'رمز عبور'): static
    {
        $value = (string) ($this->data[$field] ?? '');
        // Min 8 chars, at least 1 letter and 1 digit
        if (strlen($value) < 8 || !preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
            $this->errors[$field] = "«{$label}» باید حداقل ۸ کاراکتر، شامل حرف و عدد باشد.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "«{$label}» باید عدد باشد.";
        }
        return $this;
    }

    public function min(string $field, int|float $min, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && (float) $value < $min) {
            $this->errors[$field] = "«{$label}» نباید کمتر از {$min} باشد.";
        }
        return $this;
    }

    public function date(string $field, string $label = 'تاریخ'): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !strtotime((string) $value)) {
            $this->errors[$field] = "«{$label}» معتبر نیست.";
        }
        return $this;
    }

    public function futureDate(string $field, string $label = 'تاریخ'): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && strtotime((string) $value) < strtotime('today')) {
            $this->errors[$field] = "«{$label}» باید در آینده باشد.";
        }
        return $this;
    }

    public function inList(string $field, array $allowed, string $label): static
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "مقدار «{$label}» نامعتبر است.";
        }
        return $this;
    }

    // ── Results ───────────────────────────────────────────────

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }
}
