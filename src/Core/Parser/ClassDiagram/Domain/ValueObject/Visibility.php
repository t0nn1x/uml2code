<?php

namespace App\Core\Parser\ClassDiagram\Domain\ValueObject;

/**
 * Value object representing visibility of class members
 */
class Visibility
{
    public const PUBLIC = 'public';
    public const PRIVATE = 'private';
    public const PROTECTED = 'protected';
    public const PACKAGE = 'package';

    /**
     * @var string The visibility value
     */
    private string $value;

    /**
     * Create a new visibility
     *
     * @param string $value The visibility value
     * @throws \InvalidArgumentException If the visibility is invalid
     */
    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create a visibility from a string
     *
     * @param string $visibility The visibility string
     * @return self
     */
    public static function fromString(string $visibility): self
    {
        $visibility = strtolower(trim($visibility));

        return new self(match ($visibility) {
            '+', 'public' => self::PUBLIC,
            '-', 'private' => self::PRIVATE,
            '#', 'protected' => self::PROTECTED,
            '~', 'package' => self::PACKAGE,
            default => self::PUBLIC
        });
    }

    /**
     * Create a public visibility
     *
     * @return self
     */
    public static function public(): self
    {
        return new self(self::PUBLIC);
    }

    /**
     * Create a private visibility
     *
     * @return self
     */
    public static function private(): self
    {
        return new self(self::PRIVATE);
    }

    /**
     * Create a protected visibility
     *
     * @return self
     */
    public static function protected(): self
    {
        return new self(self::PROTECTED);
    }

    /**
     * Create a package visibility
     *
     * @return self
     */
    public static function package(): self
    {
        return new self(self::PACKAGE);
    }

    /**
     * Validate the visibility value
     *
     * @param string $value The value to validate
     * @throws \InvalidArgumentException If the value is invalid
     */
    private function validate(string $value): void
    {
        $validValues = [self::PUBLIC, self::PRIVATE, self::PROTECTED, self::PACKAGE];

        if (!in_array($value, $validValues)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid visibility "%s". Valid values are: %s', $value, implode(', ', $validValues))
            );
        }
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
