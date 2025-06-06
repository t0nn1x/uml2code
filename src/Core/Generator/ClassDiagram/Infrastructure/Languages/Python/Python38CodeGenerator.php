<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * Python 3.8 code generator for class diagrams
 *
 * Python 3.8 features:
 * - Positional-only parameters
 * - Assignment expressions (walrus operator :=)
 * - f-string debugging
 * - typing.Literal
 * - typing.Final
 * - typing.TypedDict
 */
class Python38CodeGenerator extends AbstractPythonCodeGenerator
{
    /**
     * Python 3.8 enhanced type mapping
     */
    protected const TYPE_MAPPING = [
        'string' => 'str',
        'str' => 'str',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'boolean' => 'bool',
        'bool' => 'bool',
        'void' => 'None',
        'object' => 'object',
        'any' => 'Any',
        'DateTime' => 'datetime',
        'datetime' => 'datetime',
        'Date' => 'date',
        'date' => 'date',
        'Time' => 'time',
        'time' => 'time',
        'Map' => 'Dict',
        'map' => 'Dict',
        'Dictionary' => 'Dict',
        'dict' => 'Dict',
        'List' => 'List',
        'list' => 'List',
        'Array' => 'List',
        'array' => 'List',
        'Set' => 'Set',
        'set' => 'Set',
        'Collection' => 'List',
        'collection' => 'List',
        'Tuple' => 'Tuple',
        'tuple' => 'Tuple',
        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union',
        'union' => 'Union',
        'Final' => 'Final',
        'final' => 'Final',
        'Literal' => 'Literal',
        'literal' => 'Literal',
        'byte' => 'int',
        'byte[]' => 'bytes',
        'bytes' => 'bytes',
        'long' => 'int',
        'UUID' => 'UUID',
        'uuid' => 'UUID',
        'Status' => 'str',
        'status' => 'str',
        'Callable' => 'Callable',
        'callable' => 'Callable',
    ];

    /**
     * @var int The index of the current class being generated
     */
    protected int $currentClassIndex = 0;
    
    /**
     * @inheritDoc
     */
    public function generate(): array
    {
        // Validate the diagram structure
        $this->validateDiagram();
        
        // Process each class in the diagram
        foreach ($this->diagram['classes'] as $index => $class) {
            $this->currentClassIndex = $index;
            $this->generateClass($class);
        }
        
        return $this->files;
    }
    
    /**
     * Validate the diagram structure
     *
     * @throws GeneratorException
     */
    protected function validateDiagram(): void
    {
        if (!isset($this->diagram['classes']) || !is_array($this->diagram['classes'])) {
            throw new GeneratorException('Invalid diagram structure: missing "classes" array');
        }
    }
    
    /**
     * Generate a Python class from the diagram class definition
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $name = $classData['name'] ?? 'UnnamedClass';
        $type = $classData['type'] ?? 'class';
        
        // Collect used types for imports
        $usedTypes = $this->collectUsedTypes($classData);
        
        // Generate the Python code
        $code = $this->generateFileHeader();
        $code .= $this->generateImports($usedTypes);
        $code .= $this->generateTypeVarDeclarations($classData);
        $code .= $this->generateClassCode($classData);
        
        // Create and add the code file
        $filename = $name . '.py';
        $path = $this->outputDirectory ?? '';
        $file = new CodeFile($filename, $path, $code);
        $this->addFile($file);
        
        return $file;
    }

    /**
     * Generate file header with encoding and docstring
     *
     * @return string
     */
    protected function generateFileHeader(): string
    {
        return "# -*- coding: utf-8 -*-\n\"\"\"Generated Python module for Python 3.8.\"\"\"\n\n";
    }

    /**
     * Generate module-level TypeVar declarations for generic classes
     *
     * @param array $classData
     * @return string
     */
    protected function generateTypeVarDeclarations(array $classData): string
    {
        $typeParameters = $classData['typeParameters'] ?? [];
        if (empty($typeParameters)) {
            return '';
        }
        
        $code = '';
        foreach ($typeParameters as $param) {
            $code .= "{$param} = TypeVar('{$param}')\n";
        }
        return $code . "\n";
    }

    /**
     * Generate the main class code
     *
     * @param array $classData
     * @return string
     */
    protected function generateClassCode(array $classData): string
    {
        $name = $classData['name'];
        $type = $classData['type'] ?? 'class';
        
        return match ($type) {
            'enum' => $this->generateEnum($classData),
            'interface' => $this->generateInterface($classData),
            'abstract' => $this->generateAbstractClass($classData),
            default => $this->generateConcreteClass($classData)
        };
    }

    /**
     * Generate a concrete class
     *
     * @param array $classData
     * @return string
     */
    protected function generateConcreteClass(array $classData): string
    {
        $name = $classData['name'];
        $extends = $classData['extends'] ?? null;
        $implements = $classData['implements'] ?? [];
        $typeParameters = $classData['typeParameters'] ?? [];
        
        // Build inheritance
        $inheritance = [];
        if ($extends) {
            $inheritance[] = $extends;
        }
        $inheritance = array_merge($inheritance, $implements);
        
        // Add Generic inheritance if needed
        if (!empty($typeParameters)) {
            $inheritance[] = 'Generic[' . implode(', ', $typeParameters) . ']';
        }
        
        $code = "class {$name}";
        
        if (!empty($inheritance)) {
            $code .= "(" . implode(', ', $inheritance) . ")";
        }
        
        $code .= ":\n";
        $code .= "    \"\"\"" . ucfirst($name) . " class.\"\"\"\n\n";
        
        // Generate __init__ method
        $code .= $this->generateInitMethod($classData);
        
        // Generate attributes as properties
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                $code .= $this->generateProperty($attribute);
            }
        }
        
        // Generate methods
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        // If no methods or attributes, add pass
        if (empty($classData['attributes']) && empty($classData['methods'])) {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate an abstract class
     *
     * @param array $classData
     * @return string
     */
    protected function generateAbstractClass(array $classData): string
    {
        $name = $classData['name'];
        $extends = $classData['extends'] ?? null;
        $implements = $classData['implements'] ?? [];
        
        // Build inheritance (ABC should be first)
        $inheritance = ['ABC'];
        if ($extends) {
            $inheritance[] = $extends;
        }
        $inheritance = array_merge($inheritance, $implements);
        
        $code = "class {$name}(" . implode(', ', $inheritance) . "):\n";
        $code .= "    \"\"\"Abstract {$name} class.\"\"\"\n\n";
        
        // Generate __init__ method
        $code .= $this->generateInitMethod($classData);
        
        // Generate attributes as properties
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                $code .= $this->generateProperty($attribute);
            }
        }
        
        // Generate methods (abstract methods will have @abstractmethod decorator)
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $code .= $this->generateMethod($method);
            }
        }
        
        return $code . "\n";
    }

    /**
     * Generate an interface (using ABC)
     *
     * @param array $classData
     * @return string
     */
    protected function generateInterface(array $classData): string
    {
        $name = $classData['name'];
        $extends = $classData['extends'] ?? null;
        $implements = $classData['implements'] ?? [];
        
        // Build inheritance (ABC should be first)
        $inheritance = ['ABC'];
        if ($extends) {
            $inheritance[] = $extends;
        }
        $inheritance = array_merge($inheritance, $implements);
        
        $code = "class {$name}(" . implode(', ', $inheritance) . "):\n";
        $code .= "    \"\"\"Interface {$name}.\"\"\"\n\n";
        
        // Generate methods (all abstract in interfaces)
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                $method['isAbstract'] = true; // Force abstract for interfaces
                $code .= $this->generateMethod($method);
            }
        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate an enum
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnum(array $classData): string
    {
        $name = $classData['name'];
        $enumValues = $classData['enumValues'] ?? [];
        
        // Determine enum type based on values
        $enumType = $this->detectEnumType($classData);
        $baseClass = match($enumType) {
            'int' => 'IntEnum',
            'str' => 'StrEnum',
            default => 'Enum'
        };
        
        $code = "class {$name}({$baseClass}):\n";
        $code .= "    \"\"\"Enum {$name}.\"\"\"\n\n";
        
        if (!empty($enumValues)) {
            foreach ($enumValues as $enumValue) {
                $valueName = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) ? $enumValue['value'] : null;
                
                if ($value !== null) {
                    if ($enumType === 'str') {
                        $code .= "    {$valueName} = '{$value}'\n";
                    } else {
                        $code .= "    {$valueName} = {$value}\n";
                    }
                } else {
                    // Auto-assign values for Enum
                    $code .= "    {$valueName} = auto()\n";
                }
            }
        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate __init__ method
     *
     * @param array $classData
     * @return string
     */
    protected function generateInitMethod(array $classData): string
    {
        $attributes = $classData['attributes'] ?? [];
        
        if (empty($attributes)) {
            return "    def __init__(self) -> None:\n        \"\"\"Initialize instance.\"\"\"\n        pass\n\n";
        }
        
        $code = "    def __init__(self";
        
        // Add parameters for attributes
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $this->mapType($attribute['type'] ?? 'Any');
            $code .= ", {$name}: {$type} = None";
        }
        
        $code .= ") -> None:\n";
        $code .= "        \"\"\"Initialize instance.\"\"\"\n";
        
        // Initialize attributes
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $visibility = $attribute['visibility'] ?? 'public';
            
            $pythonName = match($visibility) {
                'private' => '_' . $name,
                'protected' => '_' . $name,
                'package' => '_' . $name,
                default => $name
            };
            
            $code .= "        self.{$pythonName} = {$name}\n";
        }
        
        return $code . "\n";
    }

    /**
     * Detect enum type based on values
     *
     * @param array $classData
     * @return string
     */
    protected function detectEnumType(array $classData): string
    {
        $enumValues = $classData['enumValues'] ?? [];
        
        foreach ($enumValues as $enumValue) {
            if (is_array($enumValue) && isset($enumValue['value'])) {
                $value = $enumValue['value'];
                if (is_numeric($value)) {
                    return 'int';
                } elseif (is_string($value)) {
                    return 'str';
                }
            }
        }
        
        return 'enum'; // Basic Enum
    }

    /**
     * Collect all used types from class data for import generation
     *
     * @param array $classData
     * @return array
     */
    protected function collectUsedTypes(array $classData): array
    {
        $types = [];
        
        // Check class type
        $classType = $classData['type'] ?? 'class';
        if ($classType === 'abstract' || $classType === 'interface') {
            $types[] = 'ABC';
            $types[] = 'abstractmethod';
        } elseif ($classType === 'enum') {
            $enumType = $this->detectEnumType($classData);
            $types[] = match($enumType) {
                'int' => 'IntEnum',
                'str' => 'StrEnum',
                default => 'Enum'
            };
            if ($enumType === 'enum') {
                $types[] = 'auto';
            }
        }
        
        // Check attributes
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attribute) {
                if (isset($attribute['type'])) {
                    $allTypes = $this->extractTypesFromString($attribute['type']);
                    $types = array_merge($types, $allTypes);
                }
            }
        }
        
        // Check methods
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                if (isset($method['returnType'])) {
                    $allTypes = $this->extractTypesFromString($method['returnType']);
                    $types = array_merge($types, $allTypes);
                }
                if (!empty($method['parameters'])) {
                    foreach ($method['parameters'] as $param) {
                        if (isset($param['type'])) {
                            $allTypes = $this->extractTypesFromString($param['type']);
                            $types = array_merge($types, $allTypes);
                        }
                    }
                }
            }
        }
        
        // Extract unique types and map them
        $mappedTypes = [];
        foreach (array_unique($types) as $type) {
            // Handle special import types directly
            if (in_array($type, ['ABC', 'abstractmethod', 'Enum', 'IntEnum', 'StrEnum', 'auto', 'TypeVar', 'Callable'])) {
                $mappedTypes[] = $type;
                continue;
            }
            
            $mappedType = $this->mapType($type);
            
            // Extract all types from mapped type for import detection
            $extractedTypes = $this->extractTypesFromString($mappedType);
            foreach ($extractedTypes as $extractedType) {
                if ($extractedType !== $type && $extractedType !== 'T') { // Ignore generic parameter T
                    $mappedTypes[] = $extractedType;
                }
            }
        }
        
        // Add TypeVar and Generic if this class has generic type parameters
        if (!empty($classData['typeParameters'])) {
            $mappedTypes[] = 'TypeVar';
            $mappedTypes[] = 'Generic';
        }
        
        // Add imports for custom classes used as types
        $customTypes = [];
        foreach (array_unique($types) as $type) {
            // Check if this type refers to another class in the diagram
            if ($this->isCustomClassType($type)) {
                $customTypes[] = $type;
            }
        }
        
        // Include custom types in the mapped types for import generation
        $mappedTypes = array_merge($mappedTypes, $customTypes);
        
        return array_unique($mappedTypes);
    }

    /**
     * Check if a type refers to a custom class in the diagram
     *
     * @param string $type
     * @return bool
     */
    protected function isCustomClassType(string $type): bool
    {
        // Check if this type matches any class name in the diagram
        foreach ($this->diagram['classes'] as $class) {
            if ($class['name'] === $type) {
                return true;
            }
        }
        return false;
    }
} 
 