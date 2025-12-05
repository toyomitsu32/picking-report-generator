<?php
/**
 * ValidationResult Model
 * 
 * Represents the result of a validation operation.
 */

declare(strict_types=1);

namespace PickingReport\Models;

class ValidationResult
{
    private bool $isValid;
    private array $errors;

    public function __construct(bool $isValid = true, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->isValid = false;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrorsAsString(): string
    {
        return implode("\n", $this->errors);
    }

    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'errors' => $this->errors,
        ];
    }

    public static function success(): self
    {
        return new self(true, []);
    }

    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }
}
