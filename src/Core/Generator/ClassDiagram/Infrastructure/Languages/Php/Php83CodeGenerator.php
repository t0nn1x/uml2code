<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * PHP 8.3 code generator for class diagrams
 * 
 * New features in PHP 8.3:
 * - Typed class constants
 * - Dynamic class constant fetch
 * - #[Override] attribute
 * - Readonly amendments in anonymous classes
 * - New json_validate() function support
 * - Negative indices in arrays
 */
class Php83CodeGenerator extends Php82CodeGenerator
{
    /**
     * Enhanced type mapping for PHP 8.3 with additional type support
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
     * Generate a PHP class from the diagram class definition
     * Enhanced with PHP 8.3 improvements
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
        
        // Generate typed constants (PHP 8.3 feature)
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
     * Generate typed constants (PHP 8.3 feature)
     *
     * @param array $constants
     * @return string
     */
    protected function generateTypedConstants(array $constants): string
    {
        $code = "";
        
        foreach ($constants as $constant) {
            $name = $constant['name'];
            $value = $constant['value'];
            $type = isset($constant['type']) ? $this->mapType($constant['type']) : null;
            $visibility = $constant['visibility'] ?? 'public';
            
            $code .= "    /**\n";
            if ($type !== null) {
                $code .= "     * @var {$type}\n";
            }
            $code .= "     */\n";
            
            $code .= "    {$visibility} const ";
            
            // Add type annotation for PHP 8.3 typed constants
            if ($type !== null) {
                $code .= "{$type} ";
            }
            
            $code .= "{$name} = {$value};\n\n";
        }
        
        return $code;
    }

    /**
     * Generate methods with PHP 8.3 improvements
     * Supports #[Override] attribute and enhanced features
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
            
            // Add #[Override] attribute if specified (PHP 8.3 feature)
            if ($isOverride) {
                $code .= "    #[\\Override]\n";
            }
            
            // Method declaration
            $code .= "    {$visibility} function {$name}(";
            
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
     * Generate anonymous class support with readonly feature
     *
     * @param array $classData
     * @return string
     */
    protected function generateAnonymousClass(array $classData): string
    {
        $readonly = $classData['readonly'] ?? false;
        $implements = $classData['implements'] ?? [];
        $extends = $classData['extends'] ?? null;
        
        $code = "new ";
        
        if ($readonly) {
            $code .= "readonly ";
        }
        
        $code .= "class";
        
        if ($extends) {
            $code .= " extends {$extends}";
        }
        
        if (!empty($implements)) {
            $code .= " implements " . implode(', ', $implements);
        }
        
        $code .= "\n{\n";
        
        // Properties
        if (!empty($classData['attributes'])) {
            if ($readonly) {
                foreach ($classData['attributes'] as &$attr) {
                    $attr['readonly'] = true;
                }
            }
            $code .= $this->generateProperties($classData['attributes']);
        }
        
        // Methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethods($classData['methods'], 'class');
        }
        
        $code .= "}";
        
        return $code;
    }

    /**
     * Enhanced JSON validation support (PHP 8.3 feature)
     *
     * @param string $jsonString
     * @return bool
     */
    protected function validateJson(string $jsonString): bool
    {
        // This method would use json_validate() in PHP 8.3
        return json_validate($jsonString);
    }
} 
