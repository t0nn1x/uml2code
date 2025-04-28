<?php

namespace App\Core\Parser\Exception;

/**
 * Exception thrown when parsing UML content fails
 */
class ParserException extends \Exception
{
    /**
     * @var array Additional context for the exception
     */
    private array $context;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, array $context = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the context for the exception
     *
     * @return array The context
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
