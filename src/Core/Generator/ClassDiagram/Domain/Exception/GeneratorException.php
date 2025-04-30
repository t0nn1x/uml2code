<?php

namespace App\Core\Generator\ClassDiagram\Domain\Exception;

/**
 * Exception thrown when an error occurs during code generation
 */
class GeneratorException extends \Exception
{
    /**
     * @var array Additional context for the exception
     */
    private array $context;

    /**
     * Create a new generator exception
     *
     * @param string $message The exception message
     * @param array $context Additional context
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message, array $context = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
} 
