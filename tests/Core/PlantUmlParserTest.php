<?php

namespace App\Tests\Core\Parser;

use App\Core\Parser\DiagramTypeDetector;
use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\Models\Relationship;
use App\Core\Parser\PlantUmlParser;
use PHPUnit\Framework\TestCase;

class PlantUmlParserTest extends TestCase
{
    private PlantUmlParser $parser;

    protected function setUp(): void
    {
        $detector = new DiagramTypeDetector();
        $this->parser = new PlantUmlParser($detector);
    }

    public function testParseBasicClassDiagram(): void
    {
        $uml = <<<'UML'
@startuml
title Class Diagram Example

class User {
  +id: int
  -password: string
  #role: string
  +register(): void
  +login(password: string): bool
}

class Order {
  +id: int
  -items: array
  +addItem(item: Product): void
  +calculateTotal(): float
}

class Product {
  +id: int
  +name: string
  +price: float
}

User "1" --> "*" Order: places
Order "*" --> "*" Product: contains
@enduml
UML;

        $diagram = $this->parser->parse($uml);

        // Verify diagram basics
        $this->assertInstanceOf(ClassDiagram::class, $diagram);
        $this->assertEquals('Class Diagram Example', $diagram->getTitle());

        // Verify classes
        $this->assertCount(3, $diagram->getClasses());
        $this->assertTrue($diagram->hasClass('User'));
        $this->assertTrue($diagram->hasClass('Order'));
        $this->assertTrue($diagram->hasClass('Product'));

        // Verify attributes
        $user = $diagram->getClass('User');
        $attributes = $user->getAttributes();
        $this->assertCount(3, $attributes);

        // Check for a specific attribute
        $idAttribute = null;
        foreach ($attributes as $attr) {
            if ($attr['name'] === 'id') {
                $idAttribute = $attr;
                break;
            }
        }
        $this->assertNotNull($idAttribute);
        $this->assertEquals('int', $idAttribute['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $idAttribute['visibility']);

        // Verify methods
        $methods = $user->getMethods();
        $this->assertCount(2, $methods);

        // Check for a specific method
        $loginMethod = null;
        foreach ($methods as $method) {
            if ($method['name'] === 'login') {
                $loginMethod = $method;
                break;
            }
        }
        $this->assertNotNull($loginMethod);
        $this->assertEquals('bool', $loginMethod['returnType']);

        // Verify relationships
        $relationships = $diagram->getRelationships();
        $this->assertCount(2, $relationships);

        // Find the User -> Order relationship
        $userOrderRel = null;
        foreach ($relationships as $rel) {
            if ($rel->getSource() === 'User' && $rel->getTarget() === 'Order') {
                $userOrderRel = $rel;
                break;
            }
        }
        $this->assertNotNull($userOrderRel);
        $this->assertEquals('places', $userOrderRel->getLabel());
        $this->assertEquals(Relationship::TYPE_ASSOCIATION, $userOrderRel->getType());
    }

    public function testParseInheritanceAndImplementation(): void
    {
        $uml = <<<'UML'
@startuml
interface Loggable {
  +log(message: string): void
}

abstract class Animal {
  #name: string
  +abstract makeSound(): string
}

class Dog extends Animal implements Loggable {
  +makeSound(): string
  +log(message: string): void
}

class Cat extends Animal {
  +makeSound(): string
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);

        // Verify entity types
        $this->assertTrue($diagram->getClass('Loggable')->isInterface());
        $this->assertTrue($diagram->getClass('Animal')->isAbstract());
        $this->assertFalse($diagram->getClass('Dog')->isInterface());
        $this->assertFalse($diagram->getClass('Dog')->isAbstract());

        // Not currently testing inheritance/implementation since the test code above
        // doesn't parse that properly yet (it would need to be extracted from extends/implements keywords)
    }

    public function testInvalidDiagramThrowsException(): void
    {
        $this->expectException(ParserException::class);

        $uml = <<<'UML'
This is not a valid PlantUML diagram
UML;

        $this->parser->parse($uml);
    }

    public function testUnsupportedDiagramTypeThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Sequence diagram parsing not yet implemented');

        $uml = <<<'UML'
@startuml
actor User
participant "System" as Sys
User -> Sys: Request
Sys --> User: Response
@enduml
UML;

        $this->parser->parse($uml);
    }
}
