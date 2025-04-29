<?php

namespace App\Core\Parser\ClassDiagram\Domain\Model;

/**
 * Represents a complete class diagram
 */
class ClassDiagram
{
    /**
     * @var string|null Title of the diagram
     */
    private ?string $title = null;

    /**
     * @var ClassElement[] List of class elements in the diagram
     */
    private array $classes = [];

    /**
     * @var Relationship[] List of relationships in the diagram
     */
    private array $relationships = [];

    /**
     * Set the diagram title
     *
     * @param string|null $title The title
     * @return self
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the diagram title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Add a class element to the diagram
     *
     * @param ClassElement $class The class element to add
     * @return self
     */
    public function addClass(ClassElement $class): self
    {
        $this->classes[] = $class;
        return $this;
    }

    /**
     * Get all class elements in the diagram
     *
     * @return ClassElement[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Add a relationship to the diagram
     *
     * @param Relationship $relationship The relationship to add
     * @return self
     */
    public function addRelationship(Relationship $relationship): self
    {
        $this->relationships[] = $relationship;
        return $this;
    }

    /**
     * Get all relationships in the diagram
     *
     * @return Relationship[]
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Find a class element by name
     *
     * @param string $name The class name to find
     * @return ClassElement|null The found class or null
     */
    public function findClass(string $name): ?ClassElement
    {
        foreach ($this->classes as $class) {
            if ($class->getName() === $name) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Check if a class exists in the diagram
     *
     * @param string $name The class name to check
     * @return bool True if the class exists
     */
    public function hasClass(string $name): bool
    {
        return $this->findClass($name) !== null;
    }

    /**
     * Convert to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->title) {
            $result['title'] = $this->title;
        }

        $classes = [];
        foreach ($this->classes as $class) {
            $classes[] = $class->toArray();
        }
        $result['classes'] = $classes;

        $relationships = [];
        foreach ($this->relationships as $relationship) {
            $relationships[] = $relationship->toArray();
        }
        $result['relationships'] = $relationships;

        return $result;
    }
}
