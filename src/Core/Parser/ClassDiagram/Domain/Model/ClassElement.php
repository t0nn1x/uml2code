<?php

namespace App\Core\Parser\ClassDiagram\Domain\Model;

/**
 * Represents a class, interface, or enumeration in a UML class diagram
 */
class ClassElement
{
    /**
     * @var string Name of the class element
     */
    private string $name;

    /**
     * @var string Type of the class element (class, interface, abstract, enum)
     */
    private string $type;

    /**
     * @var Attribute[] List of attributes/properties
     */
    private array $attributes = [];

    /**
     * @var Method[] List of methods
     */
    private array $methods = [];

    /**
     * @var string|null Name of the parent class (extends)
     */
    private ?string $extends = null;

    /**
     * @var string[] List of implemented interfaces
     */
    private array $implements = [];

    /**
     * @var string[] List of generic type parameters
     */
    private array $typeParameters = [];

    /**
     * @var string|null Package/namespace this element belongs to
     */
    private ?string $package = null;

    /**
     * @var string[] List of stereotypes applied to this element
     */
    private array $stereotypes = [];

    /**
     * Create a new class element
     *
     * @param string $name Name of the class element
     * @param string $type Type of the class element
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Get the name of the class element
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of the class element
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add an attribute to the class element
     *
     * @param Attribute $attribute The attribute to add
     * @return self
     */
    public function addAttribute(Attribute $attribute): self
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * Get all attributes of the class element
     *
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Add a method to the class element
     *
     * @param Method $method The method to add
     * @return self
     */
    public function addMethod(Method $method): self
    {
        $this->methods[] = $method;
        return $this;
    }

    /**
     * Get all methods of the class element
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set the parent class
     *
     * @param string|null $extends The parent class name
     * @return self
     */
    public function setExtends(?string $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Get the parent class
     *
     * @return string|null
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * Set the implemented interfaces
     *
     * @param array $implements Array of interface names
     * @return self
     */
    public function setImplements(array $implements): self
    {
        $this->implements = $implements;
        return $this;
    }

    /**
     * Get the implemented interfaces
     *
     * @return string[]
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * Set the type parameters for generic classes
     *
     * @param array $typeParameters Array of type parameter names
     * @return self
     */
    public function setTypeParameters(array $typeParameters): self
    {
        $this->typeParameters = $typeParameters;
        return $this;
    }

    /**
     * Get the type parameters
     *
     * @return string[]
     */
    public function getTypeParameters(): array
    {
        return $this->typeParameters;
    }

    /**
     * Set the package/namespace
     *
     * @param string|null $package The package name
     * @return self
     */
    public function setPackage(?string $package): self
    {
        $this->package = $package;
        return $this;
    }

    /**
     * Get the package/namespace
     *
     * @return string|null
     */
    public function getPackage(): ?string
    {
        return $this->package;
    }

    /**
     * Set the stereotypes
     *
     * @param array $stereotypes Array of stereotype names
     * @return self
     */
    public function setStereotypes(array $stereotypes): self
    {
        $this->stereotypes = $stereotypes;
        return $this;
    }

    /**
     * Get the stereotypes
     *
     * @return string[]
     */
    public function getStereotypes(): array
    {
        return $this->stereotypes;
    }

    /**
     * Add a stereotype
     *
     * @param string $stereotype The stereotype to add
     * @return self
     */
    public function addStereotype(string $stereotype): self
    {
        if (!in_array($stereotype, $this->stereotypes)) {
            $this->stereotypes[] = $stereotype;
        }
        return $this;
    }

    /**
     * Convert to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'type' => $this->type,
        ];

        if ($this->extends) {
            $result['extends'] = $this->extends;
        }

        if (!empty($this->implements)) {
            $result['implements'] = $this->implements;
        }

        if (!empty($this->typeParameters)) {
            $result['typeParameters'] = $this->typeParameters;
        }

        if ($this->package) {
            $result['package'] = $this->package;
        }

        if (!empty($this->stereotypes)) {
            $result['stereotypes'] = $this->stereotypes;
        }

        $attributes = [];
        foreach ($this->attributes as $attribute) {
            $attributes[] = $attribute->toArray();
        }
        $result['attributes'] = $attributes;

        $methods = [];
        foreach ($this->methods as $method) {
            $methods[] = $method->toArray();
        }
        $result['methods'] = $methods;

        return $result;
    }
}
