<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * PHP 7.4 code generator for class diagrams
 */
class Php74CodeGenerator extends AbstractPhpCodeGenerator
{
    /**
     * @var int The index of the current class being generated
     */
    private int $currentClassIndex = 0;
    
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
     * Generate a PHP class from the diagram class definition
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        $fileName = "{$name}.php";
        $namespace = $this->namespacePrefix;
        
        $code = "<?php\n\n";
        $code .= "namespace {$namespace};\n\n";
        
        // Add imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports . "\n";
        }
        
        // Class, interface, or enum docblock
        $code .= "/**\n";
        $code .= " * " . ucfirst($type) . " {$name}\n";
        $code .= " */\n";
        
        // Class declaration
        switch ($type) {
            case 'interface':
                $code .= "interface {$name}\n";
                break;
                
            case 'enum':
                // PHP 7.4 doesn't support enums, so implement as class with constants
                $code .= "class {$name}\n";
                break;
                
            case 'abstract':
                $code .= "abstract class {$name}";
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                $code .= "\n";
                break;
                
            default: // regular class
                $code .= "class {$name}";
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                
                // Handle generic type parameters for classes
                if (!empty($classData['typeParameters'])) {
                    // For PHP 7.4, we'll add a docblock to document the type parameters
                    $code = str_replace("/**\n * " . ucfirst($type) . " {$name}\n */", 
                        "/**\n * " . ucfirst($type) . " {$name}\n * \n * @template " . 
                        implode("\n * @template ", $classData['typeParameters']) . "\n */", $code);
                }
                
                $code .= "\n";
                break;
        }
        
        $code .= "{\n";
        
        // Constants for enums in PHP 7.4
        if ($type === 'enum') {
            $code .= $this->generateEnumConstants($classData);
        }
        
        // Properties
        if (!empty($classData['attributes'])) {
            $code .= $this->generateProperties($classData['attributes']);
        }
        
        // Methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethods($classData['methods'], $type);
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    
    /**
     * Generate necessary import statements
     *
     * @param array $classData
     * @return string
     */
    protected function generateImports(array $classData): string
    {
        $imports = [];
        
        // Add imports for extended classes and implemented interfaces
        if (!empty($classData['extends']) && !$this->isBuiltinType($classData['extends'])) {
            $imports[] = "use {$this->namespacePrefix}\\{$classData['extends']};";
        }
        
        if (!empty($classData['implements'])) {
            foreach ($classData['implements'] as $interface) {
                if (!$this->isBuiltinType($interface)) {
                    $imports[] = "use {$this->namespacePrefix}\\{$interface};";
                }
            }
        }
        
        // Add imports for types used in attributes and methods
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attr) {
                if (isset($attr['type']) && !$this->isBuiltinType($attr['type'])) {
                    $type = $this->extractBaseType($attr['type']);
                    $imports[] = "use {$this->namespacePrefix}\\{$type};";
                }
            }
        }
        
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                // Return type imports
                if (isset($method['returnType']) && !$this->isBuiltinType($method['returnType'])) {
                    $type = $this->extractBaseType($method['returnType']);
                    $imports[] = "use {$this->namespacePrefix}\\{$type};";
                }
                
                // Parameter type imports
                if (!empty($method['parameters'])) {
                    foreach ($method['parameters'] as $param) {
                        if (isset($param['type']) && !$this->isBuiltinType($param['type'])) {
                            $type = $this->extractBaseType($param['type']);
                            $imports[] = "use {$this->namespacePrefix}\\{$type};";
                        }
                    }
                }
            }
        }
        
        // Remove duplicates and sort
        $imports = array_unique($imports);
        sort($imports);
        
        return implode("\n", $imports);
    }
    
    /**
     * Check if a type is a PHP built-in type
     *
     * @param string $type
     * @return bool
     */
    protected function isBuiltinType(string $type): bool
    {
        $builtinTypes = [
            'string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'void', 'null', 'callable', 'iterable', 'resource'
        ];
        
        $typeWithoutArrayNotation = rtrim($type, '[]');
        return in_array(strtolower($typeWithoutArrayNotation), $builtinTypes) || 
               array_key_exists(strtolower($typeWithoutArrayNotation), self::TYPE_MAPPING);
    }
    
    /**
     * Extract the base type from a potentially generic type
     *
     * @param string $type
     * @return string
     */
    protected function extractBaseType(string $type): string
    {
        // For array types like 'string[]'
        if (substr($type, -2) === '[]') {
            return $this->extractBaseType(substr($type, 0, -2));
        }
        
        // For generic types like 'List<string>'
        if (preg_match('/^(\w+)\s*<.+>$/', $type, $matches)) {
            return $matches[1];
        }
        
        return $type;
    }
    
    /**
     * Generate enum constants (for PHP 7.4 which doesn't support enums)
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnumConstants(array $classData): string
    {
        $code = "";
        
        if (empty($classData['attributes'])) {
            return $code;
        }
        
        foreach ($classData['attributes'] as $attr) {
            $name = $attr['name'];
            $visibility = $attr['visibility'] ?? 'public';
            $defaultValue = $attr['defaultValue'] ?? "'{$name}'";
            
            $code .= "    {$visibility} const {$name} = {$defaultValue};\n";
        }
        
        $code .= "\n";
        return $code;
    }
    
    /**
     * Generate properties from attributes
     *
     * @param array $attributes
     * @return string
     */
    protected function generateProperties(array $attributes): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $attr['visibility'] ?? 'public';
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : null;
            
            // Property docblock
            $code .= "    /**\n";
            
            if ($type !== null) {
                // Handle generic types in docblock
                if (isset($attr['typeArguments']) && !empty($attr['typeArguments'])) {
                    $docType = $this->generateDocblockType($attr['type'], $attr['typeArguments']);
                    $code .= "     * @var {$docType}\n";
                } else {
                    $code .= "     * @var {$type}\n";
                }
            }
            
            $code .= "     */\n";
            
            // Property declaration
            $code .= "    {$visibility} ";
            
            // Add type hint if available
            if ($type !== null && $this->isValidPropertyTypeHint($type)) {
                $code .= "{$type} ";
            }
            
            $code .= "\${$name}";
            
            // Add default value if it's a constant or simple value
            if (isset($attr['defaultValue'])) {
                $code .= " = " . $attr['defaultValue'];
            }
            
            $code .= ";\n\n";
        }
        
        return $code;
    }
    
    /**
     * Check if a type can be used as a property type hint in PHP 7.4
     *
     * @param string $type
     * @return bool
     */
    protected function isValidPropertyTypeHint(string $type): bool
    {
        // PHP 7.4 supports class/interface names and the following types for properties
        $validTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'iterable', 'self', 'parent'];
        
        return in_array(strtolower($type), $validTypes) || 
              !in_array(strtolower($type), ['void', 'callable', 'mixed']);
    }
    
    /**
     * Generate methods
     *
     * @param array $methods
     * @param string $classType
     * @return string
     */
    protected function generateMethods(array $methods, string $classType): string
    {
        $code = "";
        
        foreach ($methods as $method) {
            $name = $method['name'];
            $visibility = $method['visibility'] ?? 'public';
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : null;
            $parameters = $method['parameters'] ?? [];
            
            // Method docblock
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'mixed';
                
                // Handle generic types in docblock
                if (isset($param['typeArguments']) && !empty($param['typeArguments'])) {
                    $docType = $this->generateDocblockType($param['type'], $param['typeArguments']);
                    $code .= "     * @param {$docType} \${$paramName}\n";
                } else {
                    $code .= "     * @param {$paramType} \${$paramName}\n";
                }
            }
            
            // Return type documentation
            if ($returnType !== null) {
                $code .= "     * @return {$returnType}\n";
            }
            
            $code .= "     */\n";
            
            // Method declaration
            $code .= "    {$visibility} function {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : null;
                
                $paramStr = "";
                // Skip type hint for generic type parameters
                if ($paramType !== null && $paramType !== 'mixed' && !in_array($paramType, $this->diagram['classes'][$this->currentClassIndex]['typeParameters'] ?? [])) {
                    $paramStr .= "{$paramType} ";
                }
                
                $paramStr .= "\${$paramName}";
                $paramStrings[] = $paramStr;
            }
            
            $code .= implode(", ", $paramStrings);
            $code .= ")";
            
            // Return type
            if ($returnType !== null && $returnType !== 'mixed') {
                $code .= ": {$returnType}";
            }
            
            // Method body
            if ($classType === 'interface') {
                $code .= ";\n\n";
            } else {
                $code .= "\n    {\n";
                
                if ($returnType !== 'void' && $returnType !== null) {
                    $code .= "        // TODO: Implement method\n";
                    $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
                } else {
                    $code .= "        // TODO: Implement method\n";
                }
                
                $code .= "    }\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Get a default return value for a given type
     *
     * @param string $type
     * @return string
     */
    protected function getDefaultReturnValue(string $type): string
    {
        switch (strtolower($type)) {
            case 'string':
                return "''";
            case 'int':
            case 'float':
                return "0";
            case 'bool':
                return "false";
            case 'array':
                return "[]";
            case 'object':
                return "new \stdClass()";
            default:
                return "null";
        }
    }
    
    /**
     * Generate a docblock type from a generic type and its arguments
     *
     * @param string $baseType
     * @param array $typeArguments
     * @return string
     */
    protected function generateDocblockType(string $baseType, array $typeArguments): string
    {
        // Handle array types
        if (substr($baseType, -2) === '[]') {
            $elementType = $typeArguments[0] ?? 'mixed';
            return $elementType . '[]';
        }
        
        // Handle generic types
        $baseType = $this->extractBaseType($baseType);
        
        if (strtolower($baseType) === 'list' || strtolower($baseType) === 'array') {
            $elementType = $typeArguments[0] ?? 'mixed';
            return $elementType . '[]';
        }
        
        if (strtolower($baseType) === 'map') {
            $keyType = $typeArguments[0] ?? 'string';
            $valueType = $typeArguments[1] ?? 'mixed';
            return "array<{$keyType}, {$valueType}>";
        }
        
        // For other generic classes
        $args = implode(', ', array_map([$this, 'mapType'], $typeArguments));
        return "{$baseType}<{$args}>";
    }
} 
