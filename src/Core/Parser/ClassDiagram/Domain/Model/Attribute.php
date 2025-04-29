<?php

namespace App\Core\Parser\ClassDiagram\Domain\Model;

use App\Core\Parser\ClassDiagram\Domain\ValueObject\Visibility;
use App\Core\Parser\ClassDiagram\Domain\ValueObject\Type;

/**
 * Represents an attribute/property in a class
 */
class Attribute
{
    /**
     * @var string Name of the attribute
     */
    private string $name;

    /**
     * @var Visibility Visibility of the attribute
     */
    private Visibility $visibility;

    /**
     * @var Type|null Type of the attribute
     */
    private ?Type $type;

    /**
     * @var mixed|null Default value of the attribute
     */
    private $defaultValue = null;

    /**
     * @var bool Whether the attribute is static
     */
    private bool $isStatic = false;

    /**
     * Create a new attribute
     *
     * @param string $name Name of the attribute
     * @param Visibility $visibility Visibility of the attribute
     * @param Type|null $type Type of the attribute
     */
    public function __construct(string $name, Visibility $visibility, ?Type $type = null)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->type = $type;
    }

    /**
     * Create an attribute from parsed values
     *
     * @param string $name Name of the attribute
     * @param string $visibility Visibility as a string
     * @param string|null $type Type as a string
     * @return self
     */
    public static function fromParsed(string $name, string $visibility = 'public', ?string $type = null): self
    {
        return new self(
            $name,
            Visibility::fromString($visibility),
            $type ? Type::fromString($type) : null
        );
    }

    /**
     * Get the name of the attribute
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the visibility of the attribute
     *
     * @return Visibility
     */
    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    /**
     * Get the type of the attribute
     *
     * @return Type|null
     */
    public function getType(): ?Type
    {
        return $this->type;
    }

    /**
     * Set the default value of the attribute
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
     * Get the default value of the attribute
     *
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set whether the attribute is static
     *
     * @param bool $isStatic Whether the attribute is static
     * @return self
     */
    public function setStatic(bool $isStatic): self
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    /**
     * Check if the attribute is static
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
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

        if ($this->type) {
            // Use the full type string including generic parameters
            $result['type'] = $this->type->toString();

            // Add type arguments if this is a generic type
            $typeArgs = $this->type->getTypeArguments();
            if (!empty($typeArgs)) {
                $typeArgsArray = [];
                foreach ($typeArgs as $typeArg) {
                    $typeArgsArray[] = $typeArg->toString();
                }
                $result['typeArguments'] = $typeArgsArray;
            }
        }

        if ($this->defaultValue !== null) {
            $result['defaultValue'] = $this->defaultValue;
        }

        if ($this->isStatic) {
            $result['isStatic'] = true;
        }

        return $result;
    }
}
