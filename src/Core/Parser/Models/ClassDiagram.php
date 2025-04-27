<?php

namespace App\Core\Parser\Models;

/**
 * Represents a class diagram with entities and relationships
 */
class ClassDiagram
{
    /**
     * @var string|null
     */
    private $title;

    /**
     * @var ClassEntity[]
     */
    private $classes = [];

    /**
     * @var Relationship[]
     */
    private $relationships = [];

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * Get diagram title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set diagram title
     *
     * @param string|null $title
     * @return self
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get all classes in the diagram
     *
     * @return ClassEntity[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Add a class to the diagram
     *
     * @param ClassEntity $class
     * @return self
     */
    public function addClass(ClassEntity $class): self
    {
        $this->classes[$class->getName()] = $class;
        return $this;
    }

    /**
     * Get a class by name
     *
     * @param string $name
     * @return ClassEntity|null
     */
    public function getClass(string $name): ?ClassEntity
    {
        return $this->classes[$name] ?? null;
    }

    /**
     * Check if a class exists in the diagram
     *
     * @param string $name
     * @return bool
     */
    public function hasClass(string $name): bool
    {
        return isset($this->classes[$name]);
    }

    /**
     * Remove a class from the diagram
     *
     * @param string $name
     * @return self
     */
    public function removeClass(string $name): self
    {
        unset($this->classes[$name]);
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
     * Add a relationship to the diagram
     *
     * @param Relationship $relationship
     * @return self
     */
    public function addRelationship(Relationship $relationship): self
    {
        $this->relationships[] = $relationship;
        return $this;
    }

    /**
     * Get relationships for a specific class
     *
     * @param string $className
     * @return Relationship[]
     */
    public function getRelationshipsForClass(string $className): array
    {
        return array_filter($this->relationships, function (Relationship $relationship) use ($className) {
            return $relationship->getSource() === $className || $relationship->getTarget() === $className;
        });
    }

    /**
     * Get namespace for the diagram
     *
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Set namespace for the diagram
     *
     * @param string|null $namespace
     * @return self
     */
    public function setNamespace(?string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Get metadata for the diagram
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata for the diagram
     *
     * @param array $metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Add metadata item
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get a specific metadata value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
