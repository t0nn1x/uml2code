<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;

/**
 * Factory for creating diagram parsers
 */
class DiagramParserFactory
{
    /**
     * @var DiagramTypeDetector
     */
    private $typeDetector;

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
        $this->typeDetector = $typeDetector;
        $this->classEntityParser = $classEntityParser;
        $this->relationshipParser = $relationshipParser;
    }

    /**
     * Create a parser for the given diagram type
     *
     * @param string $diagramType The type of diagram to parse
     * @return DiagramParserInterface The appropriate parser
     * @throws ParserException If no parser exists for the diagram type
     */
    public function createParser(string $diagramType): DiagramParserInterface
    {
        switch ($diagramType) {
            case DiagramTypeDetector::TYPE_CLASS:
                return new PlantUmlClassDiagramParser(
                    $this->typeDetector,
                    $this->classEntityParser,
                    $this->relationshipParser
                );
            case DiagramTypeDetector::TYPE_SEQUENCE:
                throw new ParserException("Sequence diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_ACTIVITY:
                throw new ParserException("Activity diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_USECASE:
                throw new ParserException("Use case diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_COMPONENT:
                throw new ParserException("Component diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_STATE:
                throw new ParserException("State diagram parsing not yet implemented");
            case DiagramTypeDetector::TYPE_OBJECT:
                throw new ParserException("Object diagram parsing not yet implemented");
            default:
                throw new ParserException("No parser available for diagram type: " . $diagramType);
        }
    }
} 
