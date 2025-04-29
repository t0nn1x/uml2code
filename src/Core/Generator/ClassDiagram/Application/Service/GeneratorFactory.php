<?php

namespace App\Core\Generator\ClassDiagram\Application\Service;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeGenerator;
use App\Core\Generator\ClassDiagram\Domain\Model\Php\PhpCodeGenerator;

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
                    return new PhpCodeGenerator($diagram, $language, $version);
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
            'PHP' => ['7.4'],
            // Add more languages here as they are implemented
        ];
    }
} 
