<?php

namespace App\Core\Parser\ClassDiagram\Domain\ValueObject;

/**
 * Value object representing the type of relationship between classes
 * Enhanced with more relationship types support
 */
class RelationshipType
{
    public const ASSOCIATION = 'association';
    public const DIRECTED_ASSOCIATION = 'directedAssociation';
    public const DEPENDENCY = 'dependency';
    public const AGGREGATION = 'aggregation';
    public const COMPOSITION = 'composition';
    public const INHERITANCE = 'inheritance';
    public const IMPLEMENTATION = 'implementation';
    public const BIDIRECTIONAL = 'bidirectional';

    /**
     * @var string The relationship type value
     */
    private string $value;

    /**
     * Create a new relationship type
     *
     * @param string $value The relationship type value
     * @throws \InvalidArgumentException If the relationship type is invalid
     */
    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create a relationship type from a string
     *
     * @param string $type The relationship type string
     * @return self
     */
    public static function fromString(string $type): self
    {
        $type = strtolower(trim($type));

        return new self(match ($type) {
            'association', 'directed association', 'unidirectional' => self::ASSOCIATION,
            'directedassociation', 'directed_association' => self::DIRECTED_ASSOCIATION,
            'dependency' => self::DEPENDENCY,
            'aggregation' => self::AGGREGATION,
            'composition' => self::COMPOSITION,
            'inheritance', 'generalization', 'extends' => self::INHERITANCE,
            'implementation', 'realization', 'implements' => self::IMPLEMENTATION,
            'bidirectional', 'bidirectional_association' => self::BIDIRECTIONAL,
            default => $type
        });
    }

    /**
     * Create relationship type from UML arrow notation
     *
     * @param string $notation The UML arrow notation
     * @return self
     */
    public static function fromNotation(string $notation): self
    {
        // Bidirectional: A <--> B
        if (strpos($notation, '<-->') !== false) {
            return new self(self::BIDIRECTIONAL);
        }

        // Inheritance: A <|-- B or B --|> A
        if (strpos($notation, '<|--') !== false || strpos($notation, '--|>') !== false) {
            return new self(self::INHERITANCE);
        }

        // Implementation: A <|.. B or B ..|> A
        if (strpos($notation, '<|..') !== false || strpos($notation, '..|>') !== false) {
            return new self(self::IMPLEMENTATION);
        }

        // Composition: A *-- B or B --* A
        if (strpos($notation, '*--') !== false || strpos($notation, '--*') !== false) {
            return new self(self::COMPOSITION);
        }

        // Aggregation: A o-- B or B --o A
        if (strpos($notation, 'o--') !== false || strpos($notation, '--o') !== false) {
            return new self(self::AGGREGATION);
        }

        // Dependency: A ..> B or B <.. A
        if (strpos($notation, '..>') !== false || strpos($notation, '<..') !== false) {
            return new self(self::DEPENDENCY);
        }

        // Directed Association: A --> B or B <-- A
        if (strpos($notation, '-->') !== false || strpos($notation, '<--') !== false) {
            return new self(self::DIRECTED_ASSOCIATION);
        }

        // Association (default)
        return new self(self::ASSOCIATION);
    }

    /**
     * Validate the relationship type value
     *
     * @param string $value The value to validate
     * @throws \InvalidArgumentException If the value is invalid
     */
    private function validate(string $value): void
    {
        $validValues = [
            self::ASSOCIATION,
            self::DIRECTED_ASSOCIATION,
            self::DEPENDENCY,
            self::AGGREGATION,
            self::COMPOSITION,
            self::INHERITANCE,
            self::IMPLEMENTATION,
            self::BIDIRECTIONAL
        ];

        if (!in_array($value, $validValues)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid relationship type "%s". Valid values are: %s', $value, implode(', ', $validValues))
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
