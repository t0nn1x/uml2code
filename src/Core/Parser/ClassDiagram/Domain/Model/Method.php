<?php

namespace App\Core\Parser\ClassDiagram\Domain\Model;

use App\Core\Parser\ClassDiagram\Domain\ValueObject\Visibility;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Type;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Parameter;

/**
 * Represents a method in a class
 */
class Method
{
    /**
     * @var string Name of the method
     */
    private string $name;

    /**
     * @var Visibility Visibility of the method
     */
    private Visibility $visibility;

    /**
     * @var Parameter[] Parameters of the method
     */
    private array $parameters = [];

    /**
     * @var Type|null Return type of the method
     */
    private ?Type $returnType;

    /**
     * @var bool Whether the method is static
     */
    private bool $isStatic = false;

    /**
     * @var bool Whether the method is abstract
     */
    private bool $isAbstract = false;

    /**
     * Create a new method
     *
     * @param string $name Name of the method
     * @param Visibility $visibility Visibility of the method
     * @param Type|null $returnType Return type of the method
     */
    public function __construct(string $name, Visibility $visibility, ?Type $returnType = null)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->returnType = $returnType;
    }

    /**
     * Create a method from parsed values
     *
     * @param string $name Name of the method
     * @param string $visibility Visibility as a string
     * @param string|null $returnType Return type as a string
     * @return self
     */
    public static function fromParsed(string $name, string $visibility = 'public', ?string $returnType = null): self
    {
        return new self(
            $name,
            Visibility::fromString($visibility),
            $returnType ? Type::fromString($returnType) : null
        );
    }

    /**
     * Get the name of the method
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the visibility of the method
     *
     * @return Visibility
     */
    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    /**
     * Add a parameter to the method
     *
     * @param Parameter $parameter The parameter to add
     * @return self
     */
    public function addParameter(Parameter $parameter): self
    {
        $this->parameters[] = $parameter;
        return $this;
    }

    /**
     * Set the parameters of the method
     *
     * @param Parameter[] $parameters The parameters
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Get the parameters of the method
     *
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the return type of the method
     *
     * @return Type|null
     */
    public function getReturnType(): ?Type
    {
        return $this->returnType;
    }

    /**
     * Set whether the method is static
     *
     * @param bool $isStatic Whether the method is static
     * @return self
     */
    public function setStatic(bool $isStatic): self
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    /**
     * Check if the method is static
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * Set whether the method is abstract
     *
     * @param bool $isAbstract Whether the method is abstract
     * @return self
     */
    public function setAbstract(bool $isAbstract): self
    {
        $this->isAbstract = $isAbstract;
        return $this;
    }

    /**
     * Check if the method is abstract
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
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
            'visibility' => (string)$this->visibility,
        ];

        $params = [];
        foreach ($this->parameters as $parameter) {
            $params[] = $parameter->toArray();
        }
        $result['parameters'] = $params;

        if ($this->returnType) {
            $result['returnType'] = $this->returnType->toString();
        }

        if ($this->isStatic) {
            $result['isStatic'] = true;
        }

        if ($this->isAbstract) {
            $result['isAbstract'] = true;
        }

        return $result;
    }
}
