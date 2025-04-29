<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model;

/**
 * Base code generator model
 */
abstract class CodeGenerator
{
    /**
     * @var array The diagram data
     */
    protected array $diagram;
    
    /**
     * @var string The target language
     */
    protected string $language;
    
    /**
     * @var string The language version
     */
    protected string $version;
    
    /**
     * @var array Collection of generated code files
     */
    protected array $files = [];
    
    /**
     * @param array $diagram The diagram data
     * @param string $language The target language
     * @param string $version The language version
     */
    public function __construct(array $diagram, string $language, string $version)
    {
        $this->diagram = $diagram;
        $this->language = $language;
        $this->version = $version;
    }
    
    /**
     * Generate code from the diagram
     *
     * @return CodeFile[] Generated code files
     */
    abstract public function generate(): array;
    
    /**
     * Add a generated code file
     *
     * @param CodeFile $file
     * @return self
     */
    protected function addFile(CodeFile $file): self
    {
        $this->files[] = $file;
        return $this;
    }
    
    /**
     * Get all generated files
     *
     * @return CodeFile[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
    
    /**
     * Get files as array representation
     *
     * @return array
     */
    public function getFilesAsArray(): array
    {
        return array_map(function (CodeFile $file) {
            return $file->toArray();
        }, $this->files);
    }
} 
