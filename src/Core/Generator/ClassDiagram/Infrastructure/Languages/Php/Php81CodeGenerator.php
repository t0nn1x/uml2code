<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * PHP 8.1 code generator for class diagrams
 * 
 * New features in PHP 8.1:
 * - Native enum support
 * - Readonly properties
 * - Intersection types
 * - New in initializers
 * - First-class callable syntax
 */
class Php81CodeGenerator extends Php80CodeGenerator
{
    /**
     * Enhanced type mapping for PHP 8.1 with better type support
     */
    protected const TYPE_MAPPING = [
        'string' => 'string',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'boolean' => 'bool',
        'bool' => 'bool',
        'array' => 'array',
        'void' => 'void',
        'object' => 'object',
        'mixed' => 'mixed',
        'never' => 'never',
        'DateTime' => '\\DateTime',
        'datetime' => '\\DateTime',
        'DateTimeImmutable' => '\\DateTimeImmutable',
        'datetimeimmutable' => '\\DateTimeImmutable',
        'Map' => 'array',
        'map' => 'array',
        'List' => 'array',
        'list' => 'array',
        'Set' => 'array',
        'set' => 'array',
        'Collection' => 'array',
        'collection' => 'array',
        'Vector' => 'array',
        'vector' => 'array',
        'byte' => 'int',
        'byte[]' => 'array',
        'long' => 'int',
        'UUID' => 'string',
        'uuid' => 'string',
        'Status' => 'string',
        'status' => 'string',
    ];

    /**
     * Generate properties from attributes with PHP 8.1 features
     * Adds support for readonly properties
     *
     * @param array $attributes
     * @return string
     */
    protected function generateProperties(array $attributes): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $this->mapVisibility($attr['visibility'] ?? 'public');
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : null;
            $readonly = $attr['readonly'] ?? false;
            
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
            
            // Add readonly modifier if specified
            if ($readonly) {
                $code .= "readonly ";
            }
            
            // Add type hint
            if ($type !== null) {
                $code .= "{$type} ";
            }
            
            $code .= "\${$name}";
            
            // Add default value if it's not readonly and has a default
            if (isset($attr['defaultValue']) && !$readonly) {
                $code .= " = " . $attr['defaultValue'];
            }
            
            $code .= ";\n\n";
        }
        
        return $code;
    }

    /**
     * Generate a PHP class from the diagram class definition
     * Enhanced with PHP 8.1 enum support
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        $fileName = "{$name}.php";
        $namespace = $this->getNamespacePrefix();
        
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
                // PHP 8.1 supports native enums
                $enumType = $this->detectEnumType($classData);
                $code .= "enum {$name}";
                if ($enumType !== 'unit') {
                    $code .= ": {$enumType}";
                }
                $code .= "\n";
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
                
                // Handle generic type parameters
                if (!empty($classData['typeParameters'])) {
                    $code = str_replace("/**\n * " . ucfirst($type) . " {$name}\n */", 
                        "/**\n * " . ucfirst($type) . " {$name}\n * \n * @template " . 
                        implode("\n * @template ", $classData['typeParameters']) . "\n */", $code);
                }
                
                $code .= "\n";
                break;
        }
        
        $code .= "{\n";
        
        // Generate enum cases for PHP 8.1 native enums
        if ($type === 'enum') {
            $code .= $this->generateEnumCases($classData);
        }
        
        // Properties (not for interfaces or enums)
        if (!empty($classData['attributes']) && !in_array($type, ['interface', 'enum'])) {
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
     * Generate enum cases for PHP 8.1 native enums
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnumCases(array $classData): string
    {
        $code = "";
        $enumType = $this->detectEnumType($classData);
        
        if (!empty($classData['enumValues'])) {
            foreach ($classData['enumValues'] as $value) {
                $caseName = is_array($value) ? $value['name'] : $value;
                $caseValue = is_array($value) ? $value['value'] : null;
                
                $code .= "    case {$caseName}";
                
                if ($enumType !== 'unit' && $caseValue !== null) {
                    if ($enumType === 'string') {
                        $code .= " = '{$caseValue}'";
                    } else {
                        $code .= " = {$caseValue}";
                    }
                }
                
                $code .= ";\n";
            }
            $code .= "\n";
        }
        
        return $code;
    }

    /**
     * Detect enum type based on enum values
     *
     * @param array $classData
     * @return string
     */
    protected function detectEnumType(array $classData): string
    {
        // Check if enumType is explicitly set
        if (isset($classData['enumType'])) {
            return $classData['enumType'];
        }
        
        // Auto-detect based on values
        if (!empty($classData['enumValues'])) {
            foreach ($classData['enumValues'] as $value) {
                if (is_array($value) && isset($value['value'])) {
                    $val = $value['value'];
                    
                    // If the value is numeric, it's an int enum
                    if (is_numeric($val)) {
                        return 'int';
                    }
                    
                    // If it's not numeric and not null, it's a string enum
                    if ($val !== null) {
                        return 'string';
                    }
                }
            }
        }
        
        // Default to unit enum (no backing value) for PHP 8.1
        return 'unit';
    }

    /**
     * Generate methods with PHP 8.1 improvements
     * Supports intersection types and better type handling
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
            $visibility = $this->mapVisibility($method['visibility'] ?? 'public');
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : null;
            $parameters = $method['parameters'] ?? [];
            
            // Handle intersection types
            if (isset($method['intersectionTypes']) && !empty($method['intersectionTypes'])) {
                $returnType = implode('&', array_map([$this, 'mapType'], $method['intersectionTypes']));
            }
            
            // Method docblock
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'mixed';
                
                // Handle intersection types for parameters
                if (isset($param['intersectionTypes']) && !empty($param['intersectionTypes'])) {
                    $paramType = implode('&', array_map([$this, 'mapType'], $param['intersectionTypes']));
                }
                
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
                
                // Handle intersection types for parameters
                if (isset($param['intersectionTypes']) && !empty($param['intersectionTypes'])) {
                    $paramType = implode('&', array_map([$this, 'mapType'], $param['intersectionTypes']));
                }
                
                $paramStr = "";
                if ($paramType !== null && $paramType !== 'mixed') {
                    $paramStr .= "{$paramType} ";
                }
                
                $paramStr .= "\${$paramName}";
                
                // Default value
                if (isset($param['defaultValue'])) {
                    $paramStr .= " = " . $param['defaultValue'];
                }
                
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
} 
