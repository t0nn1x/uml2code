<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\Models\Relationship;

/**
 * PlantUML Class Diagram Parser
 */
class PlantUmlClassDiagramParser extends AbstractPlantUmlParser implements ClassDiagramParserInterface
{
    /**
     * @var ClassEntityParser
     */
    private $classEntityParser;

    /**
     * @var RelationshipParser
     */
    private $relationshipParser;

    public function __construct(
        DiagramTypeDetector $typeDetector,
        ClassEntityParser $classEntityParser,
        RelationshipParser $relationshipParser
    ) {
        parent::__construct($typeDetector);
        $this->classEntityParser = $classEntityParser;
        $this->relationshipParser = $relationshipParser;
    }

    /**
     * Parse PlantUML text into a class diagram model
     *
     * @param string $plantUmlText The PlantUML text to parse
     * @return ClassDiagram Returns a class diagram model
     * @throws ParserException If parsing fails
     */
    public function parse(string $plantUmlText): ClassDiagram
    {
        // Normalize line endings and clean input
        $plantUmlText = str_replace(["\r\n", "\r"], "\n", $plantUmlText);
        $plantUmlText = $this->cleanInput($plantUmlText);

        // Extract content between @startuml and @enduml
        $content = $this->extractDiagramContent($plantUmlText);

        // Create diagram and extract title
        $diagram = new ClassDiagram();
        [$title, $content] = $this->extractTitle($content);
        if ($title) {
            $diagram->setTitle($title);
        }

        // Parse classes and relationships
        $this->classEntityParser->parseClasses($content, $diagram);
        $this->relationshipParser->parseRelationships($content, $diagram);

        // Clean up any fragment classes after all processing is done
        $this->removeFragmentClasses($diagram);

        return $diagram;
    }

    /**
     * Remove fragment classes from the diagram
     * Fragment classes are those that appear to be substrings of existing class names
     */
    private function removeFragmentClasses(ClassDiagram $diagram): void
    {
        $classes = $diagram->getClasses();
        $classNames = [];
        $problematicClasses = [];
        
        // Collect class names
        foreach ($classes as $class) {
            $classNames[] = $class->getName();
        }
        
        // Identify potential fragment classes (substrings of other classes)
        foreach ($classNames as $className) {
            foreach ($classNames as $otherName) {
                // Skip if comparing to itself
                if ($className === $otherName) {
                    continue;
                }
                
                // If this class is a substring of another class and shorter
                if (stripos($otherName, $className) === 0 && strlen($className) < strlen($otherName)) {
                    // And this class doesn't have properties or methods defined
                    if ($diagram->hasClass($className)) {
                        $class = $diagram->getClass($className);
                        if (empty($class->getAttributes()) && empty($class->getMethods()) && 
                            empty($class->getExtends()) && empty($class->getImplements())) {
                            $problematicClasses[] = $className;
                            break;
                        }
                    }
                }
            }
        }
        
        // Remove fragment classes
        foreach (array_unique($problematicClasses) as $className) {
            $diagram->removeClass($className);
        }
    }
} 
