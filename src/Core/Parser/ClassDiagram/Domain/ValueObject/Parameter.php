<?php

namespace App\Core\Parser\ClassDiagram\Domain\ValueObject;

/**
 * Value object representing a method parameter
 */
class Parameter
{
    /**
     * @var string Name of the parameter
     */
    private string $name;

    /**
     * @var Type|null Type of the parameter
     */
    private ?Type $type;

    /**
     * @var mixed|null Default value of the parameter
     */
    private $defaultValue = null;

    /**
     * Create a new parameter
     *
     * @param string $name Name of the parameter
     * @param Type|null $type Type of the parameter
     */
    private function __construct(string $name, ?Type $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Create a parameter from parsed values
     *
     * @param string $name Name of the parameter
     * @param string|null $type Type as a string
     * @return self
     */
    public static function fromParsed(string $name, ?string $type = null): self
    {
        return new self(
            $name,
            $type ? Type::fromString($type) : null
        );
    }

    /**
     * Get the name of the parameter
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of the parameter
     *
     * @return Type|null
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * Set the default value of the parameter
     *
     * @param mixed $defaultValue The default value
     * @return self
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Get the default value of the parameter
     *
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Convert to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = ['name' => $this->name];

        if ($this->type) {
            $result['type'] = $this->type->toString();
        }

        if ($this->defaultValue !== null) {
            $result['defaultValue'] = $this->defaultValue;
        }

        return $result;
    }
}
