<?php
/**
 * ValidationException
 * 
 * Exception thrown when validation fails.
 */

declare(strict_types=1);

namespace PickingReport\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(string $message = "", array $errors = [], int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsAsString(): string
    {
        return implode("\n", $this->errors);
    }
}
