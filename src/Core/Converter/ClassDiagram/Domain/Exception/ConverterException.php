<?php

namespace App\Core\Converter\ClassDiagram\Domain\Exception;

/**
 * Exception thrown during UML to code conversion process
 */
class ConverterException extends \Exception
{
    /**
     * @var array|null Additional context for the exception
     */
    private ?array $context;

    /**
     * Create a new converter exception
     *
     * @param string $message The error message
     * @param array|null $context Additional context
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message, ?array $context = null, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context
     *
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
} 
