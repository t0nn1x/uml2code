<?php

namespace App\Core\Parser\ClassDiagram\Domain\Exception;

/**
 * Exception thrown when parsing UML content fails
 */
class ParserException extends \Exception
{
    /**
     * @var array Additional context information
     */
    private array $context;

    /**
     * Create a new parser exception
     *
     * @param string $message The error message
     * @param array $context Additional context information
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message, array $context = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the context information
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
