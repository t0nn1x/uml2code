<?php

namespace App\Core\Parser;

use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\Models\Relationship;

/**
 * Parser for relationships in PlantUML diagrams
 */
class RelationshipParser
{
    /**
     * Parse relationship definitions
     *
     * @param string $content PlantUML content
     * @param ClassDiagram $diagram The diagram to add relationships to
     */
    public function parseRelationships(string $content, ClassDiagram $diagram): void
    {
        $processedKeys = [];
        
        // Remove title line completely before processing relationships
        $content = preg_replace('/^\s*title\s+.*$/m', '', $content);
        
        // Process relationships with multiplicities in quotes
        $this->parseRelationshipsWithMultiplicities($content, $diagram, $processedKeys);
        
        // Process explicit inheritance and implementation patterns
        $this->parseInheritanceAndImplementation($content, $diagram, $processedKeys);
        
        // Process explicit 'implements' statements
        $this->parseImplementsStatements($content, $diagram, $processedKeys);
        
        // Clean up relationships
        $this->cleanupRelationships($content, $diagram);
    }

    private function parseRelationshipsWithMultiplicities(string $content, ClassDiagram $diagram, array &$processedKeys): void
    {
        // Format: Class "mult" -- "mult" Class : label
        preg_match_all('/([A-Za-z0-9_]+)\s*(?:"([^"]*)")?\s*([-.*o<>|\.]+)\s*(?:"([^"]*)")?\s*([A-Za-z0-9_]+)(?:\s*:\s*(.+))?/im', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $src = $match[1];
            $srcMul = isset($match[2]) ? $match[2] : '';
            $syntax = $match[3];
            $tgtMul = isset($match[4]) ? $match[4] : '';
            $tgt = $match[5];
            $label = isset($match[6]) ? trim($match[6]) : null;
            
            if (!$this->isValidRelationship($src, $tgt, $syntax)) {
                continue;
            }
            
            $type = $this->determineRelationshipType($syntax, $label);
            
            // Create keys for both directions to prevent duplicates
            $key1 = $this->createRelationshipKey($src, $tgt, $type, $label);
            $key2 = $this->createRelationshipKey($tgt, $src, $type, $label);
            
            if (isset($processedKeys[$key1]) || isset($processedKeys[$key2])) {
                continue;
            }
            
            $this->createAndAddRelationship($diagram, $src, $tgt, $type, $label, $srcMul, $tgtMul);
            $processedKeys[$key1] = true;
            $processedKeys[$key2] = true;
        }
    }

    private function parseInheritanceAndImplementation(string $content, ClassDiagram $diagram, array &$processedKeys): void
    {
        preg_match_all('/([A-Za-z0-9_]+)\s*(<\|\.\.|\.\.\|>|<\|--|--\|>)\s*([A-Za-z0-9_]+)(?:\s*:\s*(.+))?/im', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $src = $match[1];
            $syntax = $match[2];
            $tgt = $match[3];
            $label = isset($match[4]) ? trim($match[4]) : null;
            
            if (!$this->isValidRelationship($src, $tgt, $syntax)) {
                continue;
            }
            
            $type = (strpos($syntax, '..') !== false) ? 
                    Relationship::TYPE_IMPLEMENTATION : 
                    Relationship::TYPE_INHERITANCE;
                    
            if ($label) {
                if (strtolower($label) === 'implements') {
                    $type = Relationship::TYPE_IMPLEMENTATION;
                } else if (strtolower($label) === 'extends') {
                    $type = Relationship::TYPE_INHERITANCE;
                }
            }
            
            // Create keys for both directions to prevent duplicates
            $key1 = $this->createRelationshipKey($src, $tgt, $type, $label);
            $key2 = $this->createRelationshipKey($tgt, $src, $type, $label);
            
            if (isset($processedKeys[$key1]) || isset($processedKeys[$key2])) {
                continue;
            }
            
            $this->createAndAddRelationship($diagram, $src, $tgt, $type, $label);
            $processedKeys[$key1] = true;
            $processedKeys[$key2] = true;
        }
    }

    private function parseImplementsStatements(string $content, ClassDiagram $diagram, array &$processedKeys): void
    {
        preg_match_all('/\bclass\s+([A-Za-z][A-Za-z0-9_]*)\s+implements\s+([A-Za-z0-9_,\s]+)/i', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $className = trim($match[1]);
            $interfaces = explode(',', $match[2]);
            
            foreach ($interfaces as $interfaceName) {
                $interfaceName = trim($interfaceName);
                
                if (!empty($interfaceName) && $diagram->hasClass($className)) {
                    $key = $this->createRelationshipKey($interfaceName, $className, Relationship::TYPE_IMPLEMENTATION, null);
                    
                    if (!isset($processedKeys[$key])) {
                        $this->createAndAddRelationship($diagram, $interfaceName, $className, Relationship::TYPE_IMPLEMENTATION);
                        $processedKeys[$key] = true;
                    }
                }
            }
        }
    }

    private function cleanupRelationships(string $content, ClassDiagram $diagram): void
    {
        foreach ($diagram->getClasses() as $class) {
            $className = $class->getName();
            $implements = $class->getImplements();
            
            if (!empty($implements)) {
                $validImplements = [];
                
                foreach ($implements as $interfaceName) {
                    // Check if there's a corresponding relationship
                    $foundRelationship = false;
                    
                    foreach ($diagram->getRelationships() as $rel) {
                        if ($rel->getType() === Relationship::TYPE_IMPLEMENTATION && 
                            $rel->getSource() === $interfaceName && 
                            $rel->getTarget() === $className) {
                            $foundRelationship = true;
                            break;
                        }
                    }
                    
                    // Also check for "class X implements Y" explicit declarations
                    $foundDeclaration = preg_match('/\bclass\s+' . preg_quote($className, '/') . '\s+implements\s+(?:[A-Za-z0-9_,\s]*\s)?' . 
                                                 preg_quote($interfaceName, '/') . '(?:\s[A-Za-z0-9_,\s]*)?/i', $content);
                    
                    if ($foundRelationship || $foundDeclaration) {
                        $validImplements[] = $interfaceName;
                    }
                }
                
                // Update the class with only valid implementations
                $class->setImplements($validImplements);
            }
        }
    }

    private function isValidRelationship(string $src, string $tgt, string $syntax): bool
    {
        // Skip if syntax isn't a valid relationship pattern
        if (!$this->isValidRelationshipSyntax($syntax)) {
            return false;
        }
        
        // Skip words that are likely part of title text or metadata
        $titleWords = ['title', 'diagram', 'domain', 'model'];
        if (in_array(strtolower($src), $titleWords) || in_array(strtolower($tgt), $titleWords)) {
            return false;
        }
        
        // Skip common words that aren't likely class names
        $commonWords = ['and', 'or', 'with', 'for', 'the', 'from', 'to', 'a', 'an', 'is'];
        if (in_array(strtolower($src), $commonWords) || in_array(strtolower($tgt), $commonWords)) {
            return false;
        }
        
        // Skip primitive types
        $primitives = ['int', 'string', 'float', 'boolean', 'array', 'void', 'double', 'object'];
        if (in_array(strtolower($src), $primitives) || in_array(strtolower($tgt), $primitives)) {
            return false;
        }
        
        // Skip relationships between identical entities (likely false positive)
        if ($src === $tgt) {
            return false;
        }
        
        // Validate class name format
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $src) || !preg_match('/^[A-Z][A-Za-z0-9_]*$/', $tgt)) {
            return false;
        }
        
        return true;
    }

    private function isValidRelationshipSyntax(string $syntax): bool
    {
        return strpos($syntax, '-') !== false || 
               strpos($syntax, '.') !== false ||
               strpos($syntax, '<') !== false || 
               strpos($syntax, '>') !== false ||
               strpos($syntax, '|') !== false ||
               strpos($syntax, '*') !== false ||
               strpos($syntax, 'o') !== false;
    }

    private function determineRelationshipType(string $syntax, ?string $label): string
    {
        // Check label first for implementation/inheritance
        if ($label) {
            $labelLower = strtolower(trim($label));
            if ($labelLower === 'implements') {
                return Relationship::TYPE_IMPLEMENTATION;
            }
            if ($labelLower === 'extends') {
                return Relationship::TYPE_INHERITANCE;
            }
        }

        // Handle inheritance and implementation
        if (strpos($syntax, '<|') !== false || strpos($syntax, '|>') !== false) {
            if (strpos($syntax, '..') !== false) {
                return Relationship::TYPE_IMPLEMENTATION;
            }
            return Relationship::TYPE_INHERITANCE;
        }

        // Handle composition
        if (strpos($syntax, '*') !== false) {
            return Relationship::TYPE_COMPOSITION;
        }

        // Handle aggregation
        if (strpos($syntax, 'o') !== false) {
            return Relationship::TYPE_AGGREGATION;
        }

        // Handle dependency
        if (strpos($syntax, '.') !== false && strpos($syntax, '>') !== false) {
            return Relationship::TYPE_DEPENDENCY;
        }

        // Handle bidirectional
        if (strpos($syntax, '<') !== false && strpos($syntax, '>') !== false) {
            return Relationship::TYPE_BIDIRECTIONAL;
        }

        // Default to association
        return Relationship::TYPE_ASSOCIATION;
    }

    private function createRelationshipKey(string $source, string $target, string $type, ?string $label): string
    {
        if ($type === Relationship::TYPE_BIDIRECTIONAL) {
            $classes = [$source, $target];
            sort($classes);
            return implode(':', $classes) . ':' . $type . ($label ? ':' . $label : '');
        }
        
        return $source . ':' . $target . ':' . $type . ($label ? ':' . $label : '');
    }

    private function createAndAddRelationship(
        ClassDiagram $diagram,
        string $src,
        string $target,
        string $type,
        ?string $label = null,
        ?string $srcMul = null,
        ?string $tgtMul = null
    ): void {
        $rel = new Relationship();
        $rel->setSource($src);
        $rel->setTarget($target);
        $rel->setType($type);
        
        if ($label) {
            $rel->setLabel($label);
        }
        
        if ($srcMul) {
            $rel->setSourceMultiplicity(trim($srcMul));
        }
        
        if ($tgtMul) {
            $rel->setTargetMultiplicity(trim($tgtMul));
        }
        
        $diagram->addRelationship($rel);
        
        // Update class properties based on relationship type
        if ($type === Relationship::TYPE_IMPLEMENTATION) {
            if ($diagram->hasClass($src) && $diagram->hasClass($target)) {
                $srcClass = $diagram->getClass($src);
                $srcClass->setInterface(true);
                
                $targetClass = $diagram->getClass($target);
                $targetClass->addImplements($src);
            }
        } else if ($type === Relationship::TYPE_INHERITANCE) {
            if ($diagram->hasClass($target) && $diagram->hasClass($src)) {
                $targetClass = $diagram->getClass($target);
                $targetClass->setExtends($src);
            }
        }
    }
} 
