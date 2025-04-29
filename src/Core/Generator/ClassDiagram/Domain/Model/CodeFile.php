<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model;

/**
 * Represents a single code file to be generated
 */
class CodeFile
{
    /**
     * @var string The filename
     */
    private string $filename;

    /**
     * @var string The file path
     */
    private string $path;
    
    /**
     * @var string The code content
     */
    private string $content;
    
    /**
     * @param string $filename The filename
     * @param string $path The file path
     * @param string $content The code content
     */
    public function __construct(string $filename, string $path, string $content)
    {
        $this->filename = $filename;
        $this->path = $path;
        $this->content = $content;
    }
    
    /**
     * Get the filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
    
    /**
     * Get the file path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * Get the full file path including filename
     *
     * @return string
     */
    public function getFullPath(): string
    {
        return rtrim($this->path, '/') . '/' . $this->filename;
    }
    
    /**
     * Get the code content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
    
    /**
     * Set the code content
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'path' => $this->path,
            'content' => $this->content
        ];
    }
} 
