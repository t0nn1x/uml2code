<?php

namespace App\Core\Generator\ClassDiagram\Application\Service;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp10CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp11CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp6CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp7CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp8CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp\Csharp9CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java\Java8CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java\Java11CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java\Java17CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java\Java21CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php74CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php80CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python\Python38CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python\Python39CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python\Python310CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python\Python311CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Python\Python312CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php81CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php82CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php83CodeGenerator;
use App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php\Php84CodeGenerator;

/**
 * Factory for creating code generator instances
 */
class GeneratorFactory
{
    /**
     * Create a code generator for the specified language and version
     *
     * @param array $diagram The class diagram data
     * @param string $language The target language
     * @param string $version The language version
     * @return CodeGenerator
     * @throws GeneratorException
     */
    public function createGenerator(array $diagram, string $language, string $version): CodeGenerator
    {
        // Normalize language and version
        $language = strtoupper($language);
        
        switch ($language) {
            case 'PHP':
                if (version_compare($version, '7.4', '>=') && version_compare($version, '8.0', '<')) {
                    return new Php74CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8.0', '>=') && version_compare($version, '8.1', '<')) {
                    return new Php80CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8.1', '>=') && version_compare($version, '8.2', '<')) {
                    return new Php81CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8.2', '>=') && version_compare($version, '8.3', '<')) {
                    return new Php82CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8.3', '>=') && version_compare($version, '8.4', '<')) {
                    return new Php83CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8.4', '>=') && version_compare($version, '8.5', '<')) {
                    return new Php84CodeGenerator($diagram, $language, $version);
                }
                break;
                
            case 'JAVA':
                if (version_compare($version, '8', '>=') && version_compare($version, '11', '<')) {
                    return new Java8CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '11', '>=') && version_compare($version, '17', '<')) {
                    return new Java11CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '17', '>=') && version_compare($version, '21', '<')) {
                    return new Java17CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '21', '>=') && version_compare($version, '22', '<')) {
                    return new Java21CodeGenerator($diagram, $language, $version);
                }
                break;
                
            case 'PYTHON':
                // Handle Python versions like "3.8", "3.9", "3.10", "3.11", "3.12"
                if (version_compare($version, '3.8', '>=') && version_compare($version, '3.9', '<')) {
                    return new Python38CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '3.9', '>=') && version_compare($version, '3.10', '<')) {
                    return new Python39CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '3.10', '>=') && version_compare($version, '3.11', '<')) {
                    return new Python310CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '3.11', '>=') && version_compare($version, '3.12', '<')) {
                    return new Python311CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '3.12', '>=') && version_compare($version, '3.13', '<')) {
                    return new Python312CodeGenerator($diagram, $language, $version);
                }
                break;
            
            case 'C#':
                if (version_compare($version, '6', '>=') && version_compare($version, '7', '<')) {
                    return new Csharp6CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '7', '>=') && version_compare($version, '8', '<')) {
                    return new Csharp7CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '8', '>=') && version_compare($version, '9', '<')) {
                    return new Csharp8CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '9', '>=') && version_compare($version, '10', '<')) {
                    return new Csharp9CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '10', '>=') && version_compare($version, '11', '<')) {
                    return new Csharp10CodeGenerator($diagram, $language, $version);
                } elseif (version_compare($version, '11', '>=') && version_compare($version, '12', '<')) {
                    return new Csharp11CodeGenerator($diagram, $language, $version);
                }
                break;
            // Add cases for other languages here
        }
        
        throw new GeneratorException(
            "Unsupported language or version: {$language} {$version}",
            ['language' => $language, 'version' => $version]
        );
    }
    
    /**
     * Get supported languages and versions
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return [
            'PHP' => ['7.4', '8.0', '8.1', '8.2', '8.3', '8.4'],
            'JAVA' => ['8', '11', '17', '21'],
            'PYTHON' => ['3.8', '3.9', '3.10', '3.11', '3.12'],
            'C#' => ['6', '7', '8', '9', '10', '11'],
            // Add more languages here as they are implemented
        ];
    }
} 
