<?php

namespace App\Core\Parser\Exception;

/**
 * Exception thrown when UML parsing fails
 */
class ParserException extends \Exception
{
    // Special error codes
    public const ERROR_INVALID_SYNTAX = 100;
    public const ERROR_UNSUPPORTED_DIAGRAM = 101;
    public const ERROR_MISSING_TAGS = 102;

    /**
     * @var array Additional context about the error
     */
    private $context = [];

    /**
     * Create a new parser exception
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     * @param array $context Additional error context
     */
    public function __construct(string $message, int $code = 0, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get error context
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
