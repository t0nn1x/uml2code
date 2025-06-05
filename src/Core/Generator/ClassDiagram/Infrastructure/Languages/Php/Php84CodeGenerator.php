<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * PHP 8.4 code generator for class diagrams
 * 
 * New features in PHP 8.4:
 * - Property hooks (get/set)
 * - Asymmetric visibility
 * - new without parentheses
 * - array_find(), array_find_key(), array_any(), array_all()
 * - Multibyte trim functions
 * - Request instance variables in HTML forms
 */
class Php84CodeGenerator extends Php83CodeGenerator
{
    /**
     * Enhanced type mapping for PHP 8.4 with latest type support
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
        'null' => 'null',
        'false' => 'false',
        'true' => 'true',
        'iterable' => 'iterable',
        'callable' => 'callable',
        'resource' => 'resource',
        'DateTime' => '\\DateTime',
        'datetime' => '\\DateTime',
        'DateTimeImmutable' => '\\DateTimeImmutable',
        'datetimeimmutable' => '\\DateTimeImmutable',
        'DateTimeInterface' => '\\DateTimeInterface',
        'datetimeinterface' => '\\DateTimeInterface',
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
     * Generate properties from attributes with PHP 8.4 features
     * Adds support for property hooks and asymmetric visibility
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
            
            // PHP 8.4: Asymmetric visibility
            $getVisibility = $attr['getVisibility'] ?? $visibility;
            $setVisibility = $attr['setVisibility'] ?? $visibility;
            
            // PHP 8.4: Property hooks
            $hasGetHook = isset($attr['getHook']);
            $hasSetHook = isset($attr['setHook']);
            
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
            
            // Property declaration with asymmetric visibility
            if ($getVisibility !== $setVisibility && !$readonly) {
                $code .= "    {$getVisibility}({$setVisibility}) ";
            } else {
                $code .= "    {$visibility} ";
            }
            
            // Add readonly modifier if specified
            if ($readonly) {
                $code .= "readonly ";
            }
            
            // Add type hint
            if ($type !== null) {
                $code .= "{$type} ";
            }
            
            $code .= "\${$name}";
            
            // Property hooks (PHP 8.4 feature)
            if ($hasGetHook || $hasSetHook) {
                $code .= "\n    {\n";
                
                if ($hasGetHook) {
                    $getHookCode = $attr['getHook'];
                    $code .= "        get {\n";
                    $code .= "            {$getHookCode}\n";
                    $code .= "        }\n";
                }
                
                if ($hasSetHook) {
                    $setHookCode = $attr['setHook'];
                    $code .= "        set {\n";
                    $code .= "            {$setHookCode}\n";
                    $code .= "        }\n";
                }
                
                $code .= "    }";
            } else {
                // Add default value if it's not readonly and has a default
                if (isset($attr['defaultValue']) && !$readonly) {
                    $code .= " = " . $attr['defaultValue'];
                }
                
                $code .= ";";
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }

    /**
     * Generate a PHP class from the diagram class definition
     * Enhanced with PHP 8.4 improvements
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        $readonly = $classData['readonly'] ?? false;
        
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
                // PHP 8.1+ supports native enums
                $enumType = $this->detectEnumType($classData);
                $code .= "enum {$name}";
                if ($enumType !== 'unit') {
                    $code .= ": {$enumType}";
                }
                $code .= "\n";
                break;
                
            case 'trait':
                $code .= "trait {$name}\n";
                break;
                
            case 'abstract':
                $code .= "abstract ";
                if ($readonly) {
                    $code .= "readonly ";
                }
                $code .= "class {$name}";
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                $code .= "\n";
                break;
                
            default: // regular class
                if ($readonly) {
                    $code .= "readonly ";
                }
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
        
        // Generate typed constants (PHP 8.3+ feature)
        if (!empty($classData['constants'])) {
            $code .= $this->generateTypedConstants($classData['constants']);
        }
        
        // Generate constants for traits (PHP 8.2+ feature)
        if ($type === 'trait' && !empty($classData['constants'])) {
            $code .= $this->generateTraitConstants($classData['constants']);
        }
        
        // Generate enum cases for PHP 8.1+ native enums
        if ($type === 'enum') {
            $code .= $this->generateEnumCases($classData);
        }
        
        // Properties (not for interfaces or enums)
        if (!empty($classData['attributes']) && !in_array($type, ['interface', 'enum'])) {
            // For readonly classes, all properties become readonly automatically
            if ($readonly) {
                foreach ($classData['attributes'] as &$attr) {
                    $attr['readonly'] = true;
                }
            }
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
     * Generate methods with PHP 8.4 improvements
     * Enhanced support for latest features
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
            $isOverride = $method['override'] ?? false;
            $isStatic = $method['static'] ?? false;
            $isFinal = $method['final'] ?? false;
            $isAbstract = $method['abstract'] ?? false;
            
            // Handle DNF (Disjunctive Normal Form) types
            if (isset($method['dnfTypes']) && !empty($method['dnfTypes'])) {
                $returnType = $this->generateDNFType($method['dnfTypes']);
            }
            
            // Handle intersection types (from PHP 8.1)
            if (isset($method['intersectionTypes']) && !empty($method['intersectionTypes'])) {
                $returnType = implode('&', array_map([$this, 'mapType'], $method['intersectionTypes']));
            }
            
            // Method docblock
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'mixed';
                
                // Handle DNF types for parameters
                if (isset($param['dnfTypes']) && !empty($param['dnfTypes'])) {
                    $paramType = $this->generateDNFType($param['dnfTypes']);
                }
                
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
            
            // Add #[Override] attribute if specified (PHP 8.3+ feature)
            if ($isOverride) {
                $code .= "    #[\\Override]\n";
            }
            
            // Method declaration
            $code .= "    ";
            
            if ($isFinal) {
                $code .= "final ";
            }
            
            if ($isAbstract) {
                $code .= "abstract ";
            }
            
            $code .= "{$visibility} ";
            
            if ($isStatic) {
                $code .= "static ";
            }
            
            $code .= "function {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : null;
                $sensitive = $param['sensitive'] ?? false;
                
                // Handle DNF types for parameters
                if (isset($param['dnfTypes']) && !empty($param['dnfTypes'])) {
                    $paramType = $this->generateDNFType($param['dnfTypes']);
                }
                
                // Handle intersection types for parameters
                if (isset($param['intersectionTypes']) && !empty($param['intersectionTypes'])) {
                    $paramType = implode('&', array_map([$this, 'mapType'], $param['intersectionTypes']));
                }
                
                $paramStr = "";
                
                // Add SensitiveParameter attribute if needed (PHP 8.2+ feature)
                if ($sensitive) {
                    $paramStr = "#[\\SensitiveParameter] ";
                }
                
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
            if ($classType === 'interface' || $isAbstract) {
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
     * Generate object instantiation with PHP 8.4 "new without parentheses" feature
     *
     * @param string $className
     * @param array $arguments
     * @return string
     */
    protected function generateInstantiation(string $className, array $arguments = []): string
    {
        $code = "new {$className}";
        
        // PHP 8.4: new without parentheses when no arguments
        if (empty($arguments)) {
            // No parentheses needed in PHP 8.4
            return $code;
        }
        
        // With arguments, parentheses are still required
        $code .= "(";
        $argStrings = [];
        foreach ($arguments as $arg) {
            $argStrings[] = $arg;
        }
        $code .= implode(", ", $argStrings);
        $code .= ")";
        
        return $code;
    }

    /**
     * Generate array search methods using PHP 8.4 new array functions
     *
     * @param string $arrayVar
     * @param string $callback
     * @return array
     */
    protected function generateArraySearchMethods(string $arrayVar, string $callback): array
    {
        return [
            'find' => "array_find({$arrayVar}, {$callback})",
            'find_key' => "array_find_key({$arrayVar}, {$callback})",
            'any' => "array_any({$arrayVar}, {$callback})",
            'all' => "array_all({$arrayVar}, {$callback})"
        ];
    }

    /**
     * Enhanced default return value with PHP 8.4 improvements
     *
     * @param string $type
     * @return string
     */
    protected function getDefaultReturnValue(string $type): string
    {
        // Handle property hook references
        if (strpos($type, 'get') === 0 || strpos($type, 'set') === 0) {
            return 'null';
        }
        
        // Use parent implementation for other types
        return parent::getDefaultReturnValue($type);
    }
} 
