<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python;

/**
 * Python 3.12 code generator for class diagrams
 *
 * Python 3.12 new features:
 * - Enhanced f-strings with nested expressions and format specifiers
 * - Generic type aliases using the type statement
 * - Override decorator (@override) from typing module
 * - Performance monitoring and profiling improvements
 * - Enhanced error messages with even more precise locations
 * - Improved typing support and performance
 * - Better enum support with enhanced functionality
 * - sys.monitoring for performance analysis
 */
class Python312CodeGenerator extends Python311CodeGenerator
{
    /**
     * Python 3.12 enhanced type mapping with latest features
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
        'null' => 'None',
        'object' => 'object',
        'any' => 'Any',
        'DateTime' => 'datetime',
        'datetime' => 'datetime',
        'Date' => 'date',
        'date' => 'date',
        'Time' => 'time',
        'time' => 'time',
        // Python 3.12: Enhanced builtin generics with better performance
        'Map' => 'dict',
        'map' => 'dict',
        'Dictionary' => 'dict',
        'dict' => 'dict',
        'List' => 'list',
        'list' => 'list',
        'Array' => 'list',
        'array' => 'list',
        'Set' => 'set',
        'set' => 'set',
        'Collection' => 'list',
        'collection' => 'list',
        'Tuple' => 'tuple',
        'tuple' => 'tuple',
        // Python 3.12: Continue to prefer union operator
        'Optional' => 'Optional',
        'optional' => 'Optional',
        'Union' => 'Union',
        'union' => 'Union',
        'Final' => 'Final',
        'final' => 'Final',
        'Literal' => 'Literal',
        'literal' => 'Literal',
        'LiteralString' => 'LiteralString',
        'literalstring' => 'LiteralString',
        'Self' => 'Self',
        'self' => 'Self',
        'NotRequired' => 'NotRequired',
        'notrequired' => 'NotRequired',
        'Required' => 'Required',
        'required' => 'Required',
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
        // Python 3.12: Type alias support
        'TypeAlias' => 'TypeAlias',
        'typealias' => 'TypeAlias',
    ];

    /**
     * Common Python 3.12+ imports with latest features
     */
    protected const COMMON_IMPORTS = [
        // Python 3.12: Enhanced typing with @override
        'typing' => ['Any', 'Callable', 'Final', 'Literal', 'LiteralString', 'Optional', 'Self', 'NotRequired', 'Required', 'override', 'TypeAlias'],
        'typing_extensions' => ['TypedDict'], // Still for compatibility
        'datetime' => ['datetime', 'date', 'time'],
        'uuid' => ['UUID'],
        'enum' => ['Enum', 'IntEnum', 'StrEnum', 'auto'],
        'abc' => ['ABC', 'abstractmethod'],
        'dataclasses' => ['dataclass', 'field'],
        'collections.abc' => ['Mapping', 'Sequence', 'Iterable'],
        // Python 3.12: Enhanced monitoring and debugging
        'contextlib' => ['ExitStack', 'contextmanager'],
        'traceback' => ['print_exception'],
        'sys' => ['monitoring'], // Python 3.12 performance monitoring
    ];

    /**
     * Override file header for Python 3.12
     *
     * @return string
     */
    protected function generateFileHeader(): string
    {
        return "# -*- coding: utf-8 -*-\n\"\"\"Generated Python module for Python 3.12.\"\"\"\n\n";
    }

    /**
     * Generate the main class code with Python 3.12 stereotype support
     *
     * @param array $classData
     * @return string
     */
    protected function generateClassCode(array $classData): string
    {
        $name = $classData['name'];
        $type = $classData['type'] ?? 'class';
        $stereotypes = $classData['stereotypes'] ?? [];
        
        // Check stereotypes first, then fall back to type
        if (!empty($stereotypes)) {
            $stereotype = strtolower($stereotypes[0]);
            return match ($stereotype) {
                'typeddict' => $this->generateTypedDict($classData),
                'enum' => $this->generateEnum($classData),
                'interface' => $this->generateInterface($classData),
                'abstract' => $this->generateAbstractClass($classData),
                default => $this->generateConcreteClass($classData)
            };
        }
        
        return match ($type) {
            'enum' => $this->generateEnum($classData),
            'interface' => $this->generateInterface($classData),
            'abstract' => $this->generateAbstractClass($classData),
            default => $this->generateConcreteClass($classData)
        };
    }

    /**
     * Generate a TypedDict class for Python 3.12 with enhanced features
     *
     * @param array $classData
     * @return string
     */
    protected function generateTypedDict(array $classData): string
    {
        $name = $classData['name'];
        $attributes = $classData['attributes'] ?? [];
        
        $code = "class {$name}(TypedDict):\n";
        $code .= "    \"\"\"TypedDict {$name}.\"\"\"\n\n";
        
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute['name'];
                $attrType = $this->mapType($attribute['type'] ?? 'Any');
                $code .= "    {$attrName}: {$attrType}\n";
            }
        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }

    /**
     * Generate concrete class with Python 3.12 features
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
        
        $code = '';
        $code .= "class {$name}";
        
        // Add generic type parameters using type aliases
        if (!empty($typeParameters)) {
            $code .= "  # Generic[" . implode(', ', $typeParameters) . "]";
        }
        
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
                $code .= $this->generatePropertyWithOverride($attribute);
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
     * Generate generic type aliases using Python 3.12 type statement
     *
     * @param array $classData
     * @return string
     */
    protected function generateGenericTypeAliases(array $classData): string
    {
        $typeParameters = $classData['typeParameters'] ?? [];
        if (empty($typeParameters)) {
            return '';
        }
        
        $code = "# Python 3.12: Generic type aliases\n";
        
        // Generate common type aliases for this class
        $className = $classData['name'];
        
        // Create type aliases for common patterns
        $code .= "type {$className}List[T] = list[{$className}[T]]\n";
        $code .= "type {$className}Dict[K, V] = dict[K, {$className}[V]]\n";
        $code .= "type Optional{$className}[T] = {$className}[T] | None\n";
        
        return $code . "\n";
    }

    /**
     * Generate __init__ method with enhanced f-strings
     *
     * @param array $classData
     * @return string
     */
    protected function generateInitMethodWithEnhancedFStrings(array $classData): string
    {
        $attributes = $classData['attributes'] ?? [];
        $className = $classData['name'];
        
        if (empty($attributes)) {
            return "    def __init__(self) -> None:\n" .
                   "        \"\"\"Initialize {$className} instance.\"\"\"\n" .
                   "        pass\n\n";
        }
        
        $code = "    def __init__(self";
        
        // Add parameters for attributes
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $this->mapType($attribute['type'] ?? 'Any');
            $code .= ", {$name}: {$type} = None";
        }
        
        $code .= ") -> None:\n";
        $code .= "        \"\"\"Initialize {$className} instance.\"\"\"\n";
        
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
     * Generate property with @override decorator when appropriate
     *
     * @param array $attribute
     * @return string
     */
    protected function generatePropertyWithOverride(array $attribute): string
    {
        // Use parent implementation but could be enhanced for overrides
        return parent::generateProperty($attribute);
    }

    /**
     * Generate method with @override decorator and Python 3.12 features
     *
     * @param array $method
     * @param string|null $parentClass
     * @return string
     */
    protected function generateMethodWithOverride(array $method, ?string $parentClass = null): string
    {
        $name = $method['name'];
        $returnType = $this->mapType($method['returnType'] ?? 'None');
        $parameters = $method['parameters'] ?? [];
        $isStatic = $method['isStatic'] ?? false;
        $isAbstract = $method['isAbstract'] ?? false;
        $isOverride = $method['isOverride'] ?? false;
        
        $code = '';
        
        // Add decorators
        if ($isStatic) {
            $code .= "    @staticmethod\n";
        }
        if ($isAbstract) {
            $code .= "    @abstractmethod\n";
        }
        // Python 3.12: @override decorator for better inheritance checking
        if ($isOverride || ($parentClass && $this->isCommonMethod($name))) {
            $code .= "    @override\n";
        }
        
        // Method signature with Self type support
        $paramStr = $isStatic ? '' : 'self';
        
        foreach ($parameters as $param) {
            if ($paramStr !== '' && $paramStr !== 'self') {
                $paramStr .= ', ';
            } elseif ($paramStr === 'self') {
                $paramStr .= ', ';
            }
            
            $paramType = $this->mapType($param['type'] ?? 'Any');
            $paramStr .= $param['name'] . ': ' . $paramType;
        }
        
        // Use Self type for methods that return the same class instance
        if ($returnType === 'self' || $returnType === 'Self' || 
            ($returnType === 'object' && $this->isFluentMethod($method))) {
            $returnType = 'Self';
        }
        
        $code .= "    def {$name}({$paramStr}) -> {$returnType}:\n";
        $code .= "        \"\"\"" . ucfirst(str_replace('_', ' ', $name)) . ".\"\"\"\n";
        
        if ($isAbstract) {
            $code .= "        pass\n";
        } else {
            // Python 3.12: Enhanced f-string logging
            $code .= "        # Python 3.12: Enhanced f-string debugging\n";
            $code .= "        debug_msg = f\"Executing {{self.__class__.__name__}}.{$name}({{', '.join(f'{{k}}={{v}}' for k, v in locals().items() if k != 'self')}})\"\n";
            $code .= "        # TODO: Implement method\n";
            
            // Add exception handling with enhanced error messages
            if ($this->isComplexMethod($method)) {
                $code .= "        try:\n";
                $code .= "            # Implementation here\n";
                if ($returnType !== 'None') {
                    $defaultReturn = $this->getDefaultReturnValue($returnType);
                    $code .= "            return {$defaultReturn}\n";
                }
                $code .= "        except* (ValueError, TypeError) as eg:\n";
                $code .= "            # Python 3.12: Enhanced error messages with suggestions\n"; 
                $code .= "            enhanced_msg = f\"Multiple errors in {{self.__class__.__name__}}.{$name}: {{', '.join(str(e) for e in eg.exceptions)}}\"\n";
                $code .= "            raise RuntimeError(enhanced_msg) from eg\n";
            } else {
                if ($returnType !== 'None') {
                    $defaultReturn = $this->getDefaultReturnValue($returnType);
                    $code .= "        return {$defaultReturn}\n";
                }
            }
        }
        
        return $code . "\n";
    }

    /**
     * Generate performance monitoring example for Python 3.12
     *
     * @return string
     */
    protected function generatePerformanceMonitoringExample(): string
    {
        return "    def monitor_performance(self) -> dict[str, float]:\n" .
               "        \"\"\"Example of Python 3.12 performance monitoring.\n\n" .
               "        Uses sys.monitoring for performance analysis.\n" .
               "        \"\"\"\n" .
               "        import time\n" .
               "        from sys import monitoring\n" .
               "        \n" .
               "        start_time = time.perf_counter()\n" .
               "        \n" .
               "        # Enable monitoring for this function\n" .
               "        monitoring.use_tool_id(monitoring.PROFILER_ID, 'performance_monitor')\n" .
               "        \n" .
               "        try:\n" .
               "            # Simulate some work\n" .
               "            data = [i**2 for i in range(1000)]  # Comprehension inlining optimization\n" .
               "            result = sum(data)\n" .
               "            \n" .
               "            end_time = time.perf_counter()\n" .
               "            \n" .
               "            # Python 3.12: Enhanced f-string with nested expressions\n" .
               "            performance_info = {\n" .
               "                'execution_time': end_time - start_time,\n" .
               "                'items_processed': len(data),\n" .
               "                'result': result,\n" .
               "                'throughput': f\"{len(data) / (end_time - start_time):.2f} items/sec\"\n" .
               "            }\n" .
               "            \n" .
               "            return performance_info\n" .
               "            \n" .
               "        finally:\n" .
               "            monitoring.free_tool_id(monitoring.PROFILER_ID)\n\n";
    }

    /**
     * Check if class has performance-critical methods
     *
     * @param array $classData
     * @return bool
     */
    protected function hasPerformanceCriticalMethods(array $classData): bool
    {
        $methods = $classData['methods'] ?? [];
        $performancePatterns = ['calculate', 'compute', 'process', 'transform', 'analyze'];
        
        foreach ($methods as $method) {
            $name = strtolower($method['name'] ?? '');
            foreach ($performancePatterns as $pattern) {
                if (str_contains($name, $pattern)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if method name is commonly overridden
     *
     * @param string $methodName
     * @return bool
     */
    protected function isCommonMethod(string $methodName): bool
    {
        $commonMethods = [
            '__str__', '__repr__', '__eq__', '__hash__', '__len__',
            'toString', 'equals', 'hashCode', 'clone', 'validate'
        ];
        
        return in_array($methodName, $commonMethods);
    }

    /**
     * Collect used types with Python 3.12 additions
     *
     * @param array $classData
     * @return array
     */
    protected function collectUsedTypes(array $classData): array
    {
        $usedTypes = parent::collectUsedTypes($classData);
        
        // Check if we need override decorator
        $needsOverride = false;
        foreach ($classData['methods'] ?? [] as $method) {
            if ($method['isOverride'] ?? false || $this->isCommonMethod($method['name'] ?? '')) {
                $needsOverride = true;
                break;
            }
        }
        
        if ($needsOverride) {
            $usedTypes['override'] = true;
        }
        
        return $usedTypes;
    }

    /**
     * Get import for Python 3.12 specific types and decorators
     *
     * @param string $type
     * @return array|null
     */
    protected function getImportForType(string $type): ?array
    {
        $python312Types = [
            'override' => ['typing', 'override'],
        ];
        
        if (isset($python312Types[$type])) {
            return $python312Types[$type];
        }
        
        return parent::getImportForType($type);
    }

    /**
     * Generate enhanced enum with Python 3.12 features
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
                $description = is_array($enumValue) ? ($enumValue['description'] ?? '') : '';
                
                if ($value !== null) {
                    if ($enumType === 'str') {
                        $code .= "    {$valueName} = '{$value}'";
                    } else {
                        $code .= "    {$valueName} = {$value}";
                    }
                    
                    // Python 3.12: Enhanced enum with descriptions using f-strings
                    if ($description) {
                        $code .= "  # {$description}";
                    }
                    $code .= "\n";
                } else {
                    // Auto-assign values for Enum
                    $code .= "    {$valueName} = auto()";
                    if ($description) {
                        $code .= "  # {$description}";
                    }
                    $code .= "\n";
                }
            }
            

        } else {
            $code .= "    pass\n";
        }
        
        return $code . "\n";
    }
} 
