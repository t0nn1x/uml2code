<?php

namespace App\Tests\Core\Parser;

use App\Core\Parser\DiagramTypeDetector;
use App\Core\Parser\Exception\ParserException;
use App\Core\Parser\Models\ClassDiagram;
use App\Core\Parser\Models\ClassEntity;
use App\Core\Parser\Models\Relationship;
use App\Core\Parser\PlantUmlParser;
use App\Core\Parser\DiagramParserFactory;
use App\Core\Parser\ClassEntityParser;
use App\Core\Parser\RelationshipParser;
use PHPUnit\Framework\TestCase;

class PlantUmlParserTest extends TestCase
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
        $this->assertGreaterThanOrEqual(2, count($attributes));

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
        $this->assertGreaterThanOrEqual(1, count($methods));

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
        $this->assertEquals('1', $userOrderRel->getSourceMultiplicity());
        $this->assertEquals('*', $userOrderRel->getTargetMultiplicity());
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
    }

    public function testJavaStyleAttributes(): void
    {
        $uml = <<<'UML'
@startuml
class Product {
  +String name
  +double price
  -int stock
  #Boolean isAvailable
  ~UUID identifier
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $product = $diagram->getClass('Product');
        $attributes = $product->getAttributes();

        $this->assertGreaterThanOrEqual(4, count($attributes));

        // Find and check each attribute
        $nameAttr = $priceAttr = $stockAttr = $isAvailableAttr = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'name') $nameAttr = $attr;
            if ($attr['name'] === 'price') $priceAttr = $attr;
            if ($attr['name'] === 'stock') $stockAttr = $attr;
            if ($attr['name'] === 'isAvailable') $isAvailableAttr = $attr;
        }

        $this->assertNotNull($nameAttr, "Name attribute not found");
        $this->assertEquals('String', $nameAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $nameAttr['visibility']);

        $this->assertNotNull($priceAttr, "Price attribute not found");
        $this->assertEquals('double', $priceAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $priceAttr['visibility']);

        $this->assertNotNull($stockAttr, "Stock attribute not found");
        $this->assertEquals('int', $stockAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PRIVATE, $stockAttr['visibility']);

        $this->assertNotNull($isAvailableAttr, "isAvailable attribute not found");
        $this->assertEquals('Boolean', $isAvailableAttr['type']);
        $this->assertEquals(ClassEntity::VISIBILITY_PROTECTED, $isAvailableAttr['visibility']);
    }

    public function testComplexRelationships(): void
    {
        $uml = <<<'UML'
@startuml
class Company
class Department
class Employee

Company "1" *-- "2..5" Department: has
Department "1" o-- "*" Employee: employs

class Parent
class Child

Parent <|-- Child: extends
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $relationships = $diagram->getRelationships();

        $this->assertCount(3, $relationships);

        // Find specific relationships
        $companyDeptRel = $deptEmployeeRel = $parentChildRel = null;

        foreach ($relationships as $rel) {
            if ($rel->getSource() === 'Company' && $rel->getTarget() === 'Department') {
                $companyDeptRel = $rel;
            }
            if ($rel->getSource() === 'Department' && $rel->getTarget() === 'Employee') {
                $deptEmployeeRel = $rel;
            }
            if ($rel->getSource() === 'Parent' && $rel->getTarget() === 'Child') {
                $parentChildRel = $rel;
            }
        }

        $this->assertNotNull($companyDeptRel, "Company-Department relationship not found");
        $this->assertEquals(Relationship::TYPE_COMPOSITION, $companyDeptRel->getType());
        $this->assertEquals('has', $companyDeptRel->getLabel());
        $this->assertEquals('1', $companyDeptRel->getSourceMultiplicity());
        $this->assertEquals('2..5', $companyDeptRel->getTargetMultiplicity());

        $this->assertNotNull($deptEmployeeRel, "Department-Employee relationship not found");
        $this->assertEquals(Relationship::TYPE_AGGREGATION, $deptEmployeeRel->getType());
        $this->assertEquals('employs', $deptEmployeeRel->getLabel());
        $this->assertEquals('1', $deptEmployeeRel->getSourceMultiplicity());
        $this->assertEquals('*', $deptEmployeeRel->getTargetMultiplicity());

        $this->assertNotNull($parentChildRel, "Parent-Child relationship not found");
        $this->assertEquals(Relationship::TYPE_INHERITANCE, $parentChildRel->getType());
        $this->assertEquals('extends', $parentChildRel->getLabel());
    }

    public function testMethodParametersAndReturnTypes(): void
    {
        $uml = <<<'UML'
@startuml
class Calculator {
  +add(a: int, b: int): int
  +subtract(a: int, b: int): int
  +multiply(a: int, b: int): int
  +divide(a: int, b: int): float
  -validateInput(input: array): boolean
  #applyOperation(operation: string, values: array): mixed
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $calculator = $diagram->getClass('Calculator');
        $methods = $calculator->getMethods();

        $this->assertCount(6, $methods);

        // Find and check methods
        $addMethod = $divideMethod = $validateMethod = null;

        foreach ($methods as $method) {
            if ($method['name'] === 'add') $addMethod = $method;
            if ($method['name'] === 'divide') $divideMethod = $method;
            if ($method['name'] === 'validateInput') $validateMethod = $method;
        }

        $this->assertNotNull($addMethod, "Add method not found");
        $this->assertEquals('int', $addMethod['returnType']);
        $this->assertEquals('a: int, b: int', $addMethod['parameters']);
        $this->assertEquals(ClassEntity::VISIBILITY_PUBLIC, $addMethod['visibility']);

        $this->assertNotNull($divideMethod, "Divide method not found");
        $this->assertEquals('float', $divideMethod['returnType']);

        $this->assertNotNull($validateMethod, "ValidateInput method not found");
        $this->assertEquals(ClassEntity::VISIBILITY_PRIVATE, $validateMethod['visibility']);
    }

    public function testEmptyClasses(): void
    {
        $uml = <<<'UML'
@startuml
class EmptyClass
interface EmptyInterface
abstract class EmptyAbstractClass

EmptyClass --> EmptyInterface: implements
EmptyClass --> EmptyAbstractClass: extends
@enduml
UML;

        $diagram = $this->parser->parse($uml);

        $this->assertTrue($diagram->hasClass('EmptyClass'));
        $this->assertTrue($diagram->hasClass('EmptyInterface'));
        $this->assertTrue($diagram->hasClass('EmptyAbstractClass'));

        $emptyClass = $diagram->getClass('EmptyClass');
        $emptyInterface = $diagram->getClass('EmptyInterface');
        $emptyAbstractClass = $diagram->getClass('EmptyAbstractClass');

        $this->assertEmpty($emptyClass->getAttributes());
        $this->assertEmpty($emptyClass->getMethods());

        $this->assertTrue($emptyInterface->isInterface());
        $this->assertTrue($emptyAbstractClass->isAbstract());

        $relationships = $diagram->getRelationships();
        $this->assertCount(2, $relationships);
    }

    public function testMultipleMethodsAndAttributes(): void
    {
        $uml = <<<'UML'
@startuml
class User {
  +id: int
  -username: string
  -email: string
  -password: string
  -createdAt: datetime
  -updatedAt: datetime
  +constructor(username: string, email: string, password: string): void
  +getId(): int
  +getUsername(): string
  +getEmail(): string
  +setEmail(email: string): void
  +verifyPassword(password: string): bool
  -hashPassword(password: string): string
  -validateEmail(email: string): bool
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $user = $diagram->getClass('User');

        $attributes = $user->getAttributes();
        $methods = $user->getMethods();

        $this->assertCount(6, $attributes, "Expected 6 attributes: id, username, email, password, createdAt, updatedAt");
        $this->assertCount(8, $methods, "Expected 8 methods: constructor, getId, getUsername, getEmail, setEmail, verifyPassword, hashPassword, validateEmail");

        // Verify public and private methods exist
        $publicMethods = $privateMethods = 0;
        foreach ($methods as $method) {
            if ($method['visibility'] === ClassEntity::VISIBILITY_PUBLIC) {
                $publicMethods++;
            } else if ($method['visibility'] === ClassEntity::VISIBILITY_PRIVATE) {
                $privateMethods++;
            }
        }

        $this->assertEquals(6, $publicMethods, "Expected 6 public methods: constructor, getId, getUsername, getEmail, setEmail, verifyPassword");
        $this->assertEquals(2, $privateMethods, "Expected 2 private methods: hashPassword, validateEmail");
    }

    public function testEnumWithValues(): void
    {
        $uml = <<<'UML'
@startuml
enum Status {
  PENDING
  APPROVED
  REJECTED
  CANCELED
  +toString(): string
}
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $status = $diagram->getClass('Status');

        $this->assertTrue($status->isEnum());

        // Methods in enum
        $methods = $status->getMethods();
        $this->assertCount(1, $methods);
        $this->assertEquals('toString', $methods[0]['name']);

        // Enum values should be parsed as attributes without type
        $attributes = $status->getAttributes();
        $this->assertGreaterThanOrEqual(4, count($attributes));

        // Find enum values
        $pendingValue = $approvedValue = $rejectedValue = $canceledValue = null;

        foreach ($attributes as $attr) {
            if ($attr['name'] === 'PENDING') $pendingValue = $attr;
            if ($attr['name'] === 'APPROVED') $approvedValue = $attr;
            if ($attr['name'] === 'REJECTED') $rejectedValue = $attr;
            if ($attr['name'] === 'CANCELED') $canceledValue = $attr;
        }

        $this->assertNotNull($pendingValue, "PENDING enum value not found");
        $this->assertNotNull($approvedValue, "APPROVED enum value not found");
        $this->assertNotNull($rejectedValue, "REJECTED enum value not found");
        $this->assertNotNull($canceledValue, "CANCELED enum value not found");
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

    public function testMissingStartEndTagsThrowsException(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Invalid PlantUML: missing @startuml/@enduml tags');

        $uml = <<<'UML'
class User {
  +id: int
}
UML;

        $this->parser->parse($uml);
    }

    public function testCommentsAreIgnored(): void
    {
        $uml = <<<'UML'
@startuml
' This is a comment
class User {
  ' This comment describes the ID
  +id: int
  ' This is the password field
  -password: string
}

' Relationship comment
User "1" --> "*" Order
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $this->assertTrue($diagram->hasClass('User'));

        $user = $diagram->getClass('User');
        $attributes = $user->getAttributes();

        // Comments should be ignored, only actual attributes counted
        $this->assertCount(2, $attributes);
    }

    public function testDiagramWithTitle(): void
    {
        $uml = <<<'UML'
@startuml
title E-commerce System Class Diagram

class Product
class Customer
@enduml
UML;

        $diagram = $this->parser->parse($uml);
        $this->assertEquals('E-commerce System Class Diagram', $diagram->getTitle());
    }
}
