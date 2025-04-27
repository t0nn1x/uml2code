<?php

namespace App\Tests\Core\Parser;

use App\Core\Parser\DiagramTypeDetector;
use App\Core\Parser\Models\Relationship;
use App\Core\Parser\PlantUmlParser;
use App\Core\Parser\DiagramParserFactory;
use App\Core\Parser\ClassEntityParser;
use App\Core\Parser\RelationshipParser;
use PHPUnit\Framework\TestCase;

class RelationshipParsingTest extends TestCase
{
    private PlantUmlParser $parser;

    protected function setUp(): void
    {
        $detector = new DiagramTypeDetector();
        $classEntityParser = new ClassEntityParser();
        $relationshipParser = new RelationshipParser();
        $factory = new DiagramParserFactory($detector, $classEntityParser, $relationshipParser);
        $this->parser = new PlantUmlParser($detector, $factory);
    }

    /**
     * Test parsing of all the basic relationship types
     */
    public function testBasicRelationshipTypes(): void
    {
        $uml = <<<'UML'
@startuml
' Association
ClassA -- ClassB

' Directional association
ClassC --> ClassD

' Inheritance
ParentClass <|-- ChildClass

' Implementation
Interface <|.. ConcreteClass

' Aggregation
Container o-- Component

' Composition
Whole *-- Part

' Dependency
Client ..> Supplier

' Bidirectional
ClassE <--> ClassF
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $relationships = $diagram->getRelationships();

        // Check count - should be 8 relationships
        $this->assertCount(8, $relationships);

        // Find each type of relationship
        $association = $directionalAssoc = $inheritance = $implementation = null;
        $aggregation = $composition = $dependency = $bidirectional = null;

        foreach ($relationships as $rel) {
            if ($rel->getSource() === 'ClassA' && $rel->getTarget() === 'ClassB') {
                $association = $rel;
            } elseif ($rel->getSource() === 'ClassC' && $rel->getTarget() === 'ClassD') {
                $directionalAssoc = $rel;
            } elseif ($rel->getSource() === 'ParentClass' && $rel->getTarget() === 'ChildClass') {
                $inheritance = $rel;
            } elseif ($rel->getSource() === 'Interface' && $rel->getTarget() === 'ConcreteClass') {
                $implementation = $rel;
            } elseif ($rel->getSource() === 'Container' && $rel->getTarget() === 'Component') {
                $aggregation = $rel;
            } elseif ($rel->getSource() === 'Whole' && $rel->getTarget() === 'Part') {
                $composition = $rel;
            } elseif ($rel->getSource() === 'Client' && $rel->getTarget() === 'Supplier') {
                $dependency = $rel;
            } elseif ($rel->getSource() === 'ClassE' && $rel->getTarget() === 'ClassF') {
                $bidirectional = $rel;
            }
        }

        // Verify all relationships were found
        $this->assertNotNull($association, "Association relationship not found");
        $this->assertNotNull($directionalAssoc, "Directional association not found");
        $this->assertNotNull($inheritance, "Inheritance relationship not found");
        $this->assertNotNull($implementation, "Implementation relationship not found");
        $this->assertNotNull($aggregation, "Aggregation relationship not found");
        $this->assertNotNull($composition, "Composition relationship not found");
        $this->assertNotNull($dependency, "Dependency relationship not found");
        $this->assertNotNull($bidirectional, "Bidirectional relationship not found");

        // Check relationship types
        $this->assertEquals(Relationship::TYPE_ASSOCIATION, $association->getType());
        $this->assertEquals(Relationship::TYPE_ASSOCIATION, $directionalAssoc->getType());
        $this->assertEquals(Relationship::TYPE_INHERITANCE, $inheritance->getType());
        $this->assertEquals(Relationship::TYPE_IMPLEMENTATION, $implementation->getType());
        $this->assertEquals(Relationship::TYPE_AGGREGATION, $aggregation->getType());
        $this->assertEquals(Relationship::TYPE_COMPOSITION, $composition->getType());
        $this->assertEquals(Relationship::TYPE_DEPENDENCY, $dependency->getType());
        $this->assertEquals(Relationship::TYPE_BIDIRECTIONAL, $bidirectional->getType());
    }

    /**
     * Test parsing of relationships with multiplicity
     */
    public function testRelationshipsWithMultiplicity(): void
    {
        $uml = <<<'UML'
@startuml
' Basic multiplicity
Person "1" -- "1..*" Address

' Different multiplicity formats
Student "1" -- "*" Course
Professor "1..*" -- "1..*" Class
Department "0..1" -- "5" Employee

' Zero or more, one or more
Book "0..*" -- "1..*" Author

' Multiplicity with labels
Order "1" -- "many" Item : contains
Customer "1" -- "0..n" Payment : makes

' Composition with multiplicity
University "1" *-- "many" Faculty
Company "1" *-- "2..10" Division

' Inheritance without multiplicity
Animal <|-- Dog
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $relationships = $diagram->getRelationships();

        // Should be 10 relationships
        $this->assertCount(10, $relationships);

        // Find specific relationships to test
        $personAddress = $studentCourse = $orderItem = $universityFaculty = $animalDog = null;

        foreach ($relationships as $rel) {
            if ($rel->getSource() === 'Person' && $rel->getTarget() === 'Address') {
                $personAddress = $rel;
            } elseif ($rel->getSource() === 'Student' && $rel->getTarget() === 'Course') {
                $studentCourse = $rel;
            } elseif ($rel->getSource() === 'Order' && $rel->getTarget() === 'Item') {
                $orderItem = $rel;
            } elseif ($rel->getSource() === 'University' && $rel->getTarget() === 'Faculty') {
                $universityFaculty = $rel;
            } elseif ($rel->getSource() === 'Animal' && $rel->getTarget() === 'Dog') {
                $animalDog = $rel;
            }
        }

        // Verify relationships were found
        $this->assertNotNull($personAddress, "Person-Address relationship not found");
        $this->assertNotNull($studentCourse, "Student-Course relationship not found");
        $this->assertNotNull($orderItem, "Order-Item relationship not found");
        $this->assertNotNull($universityFaculty, "University-Faculty relationship not found");
        $this->assertNotNull($animalDog, "Animal-Dog relationship not found");

        // Check multiplicity
        $this->assertEquals('1', $personAddress->getSourceMultiplicity());
        $this->assertEquals('1..*', $personAddress->getTargetMultiplicity());

        $this->assertEquals('1', $studentCourse->getSourceMultiplicity());
        $this->assertEquals('*', $studentCourse->getTargetMultiplicity());

        $this->assertEquals('1', $orderItem->getSourceMultiplicity());
        $this->assertEquals('many', $orderItem->getTargetMultiplicity());
        $this->assertEquals('contains', $orderItem->getLabel());

        $this->assertEquals('1', $universityFaculty->getSourceMultiplicity());
        $this->assertEquals('many', $universityFaculty->getTargetMultiplicity());
        $this->assertEquals(Relationship::TYPE_COMPOSITION, $universityFaculty->getType());

        // Inheritance should have no multiplicity
        $this->assertNull($animalDog->getSourceMultiplicity());
        $this->assertNull($animalDog->getTargetMultiplicity());
        $this->assertEquals(Relationship::TYPE_INHERITANCE, $animalDog->getType());
    }

    /**
     * Test parsing relationships with alternative notations
     */
    public function testAlternativeRelationshipNotations(): void
    {
        $uml = <<<'UML'
@startuml
' Different ways to show associations
ClassA - ClassB
ClassC -- ClassD
ClassE --- ClassF

' Different ways to show directional associations
ClassG -> ClassH
ClassI --> ClassJ
ClassK ---> ClassL

' Different dependency notations
ClassM ..> ClassN
ClassO ....> ClassP

' Different inheritance notations
BaseClass <|- DerivedClass
Interface <|.. ImplementingClass

' Different aggregation notations
Whole o- Part
Container o-- Content

' Different composition notations
CompositeA *- ComponentA
CompositeB *-- ComponentB
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $relationships = $diagram->getRelationships();

        // Group relationships by type
        $associations = $directionals = $dependencies = $inheritances = $aggregations = $compositions = [];

        foreach ($relationships as $rel) {
            switch ($rel->getType()) {
                case Relationship::TYPE_ASSOCIATION:
                    $associations[] = $rel;
                    break;
                case Relationship::TYPE_DEPENDENCY:
                    $dependencies[] = $rel;
                    break;
                case Relationship::TYPE_INHERITANCE:
                    $inheritances[] = $rel;
                    break;
                case Relationship::TYPE_IMPLEMENTATION:
                    $implementations[] = $rel;
                    break;
                case Relationship::TYPE_AGGREGATION:
                    $aggregations[] = $rel;
                    break;
                case Relationship::TYPE_COMPOSITION:
                    $compositions[] = $rel;
                    break;
            }
        }

        // Count by type - some might be classified differently based on implementation
        $this->assertGreaterThanOrEqual(6, count($associations), "Expected at least 6 association relationships");
        $this->assertGreaterThanOrEqual(2, count($dependencies), "Expected at least 2 dependency relationships");
        $this->assertGreaterThanOrEqual(1, count($inheritances), "Expected at least 1 inheritance relationship");
        $this->assertGreaterThanOrEqual(1, count($aggregations), "Expected at least 1 aggregation relationship");
        $this->assertGreaterThanOrEqual(2, count($compositions), "Expected at least 2 composition relationships");
    }

    /**
     * Test relationships with custom labels
     */
    public function testRelationshipsWithLabels(): void
    {
        $uml = <<<'UML'
@startuml
' Labeled relationships
User -- Account : has
Employee -- Department : works in
Teacher -- Student : teaches
Product -- Category : belongs to
OrderDetail -- Product : references
Car *-- Engine : contains
Driver -- Car : drives
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $relationships = $diagram->getRelationships();

        // Check relationship labels
        $userAccount = $employeeDept = $carEngine = null;

        foreach ($relationships as $rel) {
            if ($rel->getSource() === 'User' && $rel->getTarget() === 'Account') {
                $userAccount = $rel;
            } elseif ($rel->getSource() === 'Employee' && $rel->getTarget() === 'Department') {
                $employeeDept = $rel;
            } elseif ($rel->getSource() === 'Car' && $rel->getTarget() === 'Engine') {
                $carEngine = $rel;
            }
        }

        $this->assertNotNull($userAccount, "User-Account relationship not found");
        $this->assertEquals('has', $userAccount->getLabel());

        $this->assertNotNull($employeeDept, "Employee-Department relationship not found");
        $this->assertEquals('works in', $employeeDept->getLabel());

        $this->assertNotNull($carEngine, "Car-Engine relationship not found");
        $this->assertEquals('contains', $carEngine->getLabel());
        $this->assertEquals(Relationship::TYPE_COMPOSITION, $carEngine->getType());
    }
}
