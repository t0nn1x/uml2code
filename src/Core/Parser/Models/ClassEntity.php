<?php

namespace App\Core\Parser\Models;

/**
 * Represents a class, interface, or enum in a class diagram
 */
class ClassEntity
{
    // Visibility constants
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PROTECTED = 'protected';
    public const VISIBILITY_PACKAGE = 'package';

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isAbstract = false;

    /**
     * @var bool
     */
    private $isInterface = false;

    /**
     * @var bool
     */
    private $isEnum = false;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string|null
     */
    private $extends;

    /**
     * @var string[]
     */
    private $implements = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * Get class name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set class name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Check if class is abstract
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * Set abstract flag
     *
     * @param bool $isAbstract
     * @return self
     */
    public function setAbstract(bool $isAbstract): self
    {
        $this->isAbstract = $isAbstract;
        return $this;
    }

    /**
     * Check if entity is an interface
     *
     * @return bool
     */
    public function isInterface(): bool
    {
        return $this->isInterface;
    }

    /**
     * Set interface flag
     *
     * @param bool $isInterface
     * @return self
     */
    public function setInterface(bool $isInterface): self
    {
        $this->isInterface = $isInterface;
        return $this;
    }

    /**
     * Check if entity is an enum
     *
     * @return bool
     */
    public function isEnum(): bool
    {
        return $this->isEnum;
    }

    /**
     * Set enum flag
     *
     * @param bool $isEnum
     * @return self
     */
    public function setEnum(bool $isEnum): self
    {
        $this->isEnum = $isEnum;
        return $this;
    }

    /**
     * Get namespace
     *
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Set namespace
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
     * Get extended class name
     *
     * @return string|null
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * Set extended class name
     *
     * @param string|null $extends
     * @return self
     */
    public function setExtends(?string $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Get implemented interfaces
     *
     * @return string[]
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * Set implemented interfaces
     *
     * @param string[] $implements
     * @return self
     */
    public function setImplements(array $implements): self
    {
        $this->implements = $implements;
        return $this;
    }

    /**
     * Add an implemented interface
     *
     * @param string $interface
     * @return self
     */
    public function addImplements(string $interface): self
    {
        if (!in_array($interface, $this->implements)) {
            $this->implements[] = $interface;
        }
        return $this;
    }

    /**
     * Get all attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Add an attribute
     *
     * @param array $attribute
     * @return self
     */
    public function addAttribute(array $attribute): self
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * Get all methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Add a method
     *
     * @param array $method
     * @return self
     */
    public function addMethod(array $method): self
    {
        $this->methods[] = $method;
        return $this;
    }

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata
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

    /**
     * Get entity type as string
     *
     * @return string
     */
    public function getType(): string
    {
        if ($this->isInterface) {
            return 'interface';
        } elseif ($this->isEnum) {
            return 'enum';
        } elseif ($this->isAbstract) {
            return 'abstract class';
        } else {
            return 'class';
        }
    }
}
