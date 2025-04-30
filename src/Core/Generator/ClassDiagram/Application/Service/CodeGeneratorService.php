<?php

namespace App\Core\Generator\ClassDiagram\Application\Service;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\PhpCodeGeneratorInterface;
use App\Core\Generator\ClassDiagram\Domain\Model\Languages\JavaCodeGeneratorInterface;
use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;

/**
 * Service for generating code from class diagrams
 */
class CodeGeneratorService
{
    /**
     * @var string Base output directory for generated code
     */
    private string $outputDirectory;
    
    /**
     * @var string Base namespace prefix for generated code
     */
    private string $namespacePrefix;
    
    /**
     * @var string Base package name for Java code
     */
    private string $packageName;
    
    /**
     * @var GeneratorFactory The generator factory
     */
    private GeneratorFactory $factory;
    
    /**
     * Create a new code generator service
     *
     * @param string $outputDirectory Base output directory for generated code
     * @param string $namespacePrefix Base namespace prefix for PHP code
     * @param string $packageName Base package name for Java code
     */
    public function __construct(
        string $outputDirectory = 'generated', 
        string $namespacePrefix = 'App\\Generated',
        string $packageName = 'com.example.generated'
    ) {
        $this->outputDirectory = $outputDirectory;
        $this->namespacePrefix = $namespacePrefix;
        $this->packageName = $packageName;
        $this->factory = new GeneratorFactory();
    }
    
    /**
     * Generate code from a class diagram
     *
     * @param array $diagram The class diagram data
     * @param string $language The target language (e.g., "PHP")
     * @param string $version The language version (e.g., "7.4")
     * @return array The generated code files
     * @throws GeneratorException
     */
    public function generateCode(array $diagram, string $language, string $version): array
    {
        // Create the appropriate generator based on language and version
        $generator = $this->factory->createGenerator($diagram, $language, $version);
        
        // Configure the generator based on its type
        if ($generator instanceof LanguageCodeGenerator) {
            $generator->setOutputDirectory($this->outputDirectory);
            
            // Additional language-specific configuration
            if ($generator instanceof PhpCodeGeneratorInterface) {
                $generator->setNamespacePrefix($this->namespacePrefix);
            } elseif ($generator instanceof JavaCodeGeneratorInterface) {
                $generator->setPackageName($this->packageName);
            }
        }
        
        // Generate code
        $generator->generate();
        
        // Return the generated files in array format
        return $generator->getFilesAsArray();
    }
    
    /**
     * Get supported languages and versions
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return $this->factory->getSupportedLanguages();
    }
    
    /**
     * Set the base output directory for generated code
     *
     * @param string $outputDirectory
     * @return self
     */
    public function setOutputDirectory(string $outputDirectory): self
    {
        $this->outputDirectory = $outputDirectory;
        return $this;
    }
    
    /**
     * Set the base namespace prefix for PHP code
     *
     * @param string $namespacePrefix
     * @return self
     */
    public function setNamespacePrefix(string $namespacePrefix): self
    {
        $this->namespacePrefix = $namespacePrefix;
        return $this;
    }
    
    /**
     * Set the base package name for Java code
     *
     * @param string $packageName
     * @return self
     */
    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;
        return $this;
    }
} 
