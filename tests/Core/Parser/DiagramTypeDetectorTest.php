<?php

namespace App\Tests\Core\Parser;

use App\Core\Parser\DiagramTypeDetector;
use App\Core\Parser\PlantUmlParser;
use App\Core\Parser\DiagramParserFactory;
use App\Core\Parser\ClassEntityParser;
use App\Core\Parser\RelationshipParser;
use PHPUnit\Framework\TestCase;

class DiagramTypeDetectorTest extends TestCase
{
    private DiagramTypeDetector $detector;
    private PlantUmlParser $parser;

    protected function setUp(): void
    {
        $this->detector = new DiagramTypeDetector();
        $classEntityParser = new ClassEntityParser();
        $relationshipParser = new RelationshipParser();
        $factory = new DiagramParserFactory($this->detector, $classEntityParser, $relationshipParser);
        $this->parser = new PlantUmlParser($this->detector, $factory);
    }

    public function testDetectClassDiagram(): void
    {
        $uml = <<<'UML'
@startuml
class User {
  +id: int
  +name: string
  +email: string
  +register(): void
  +login(password: string): bool
}

class Order {
  +id: int
  +date: DateTime
  +status: string
}

User "1" --> "*" Order: places
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_CLASS, $type);
    }

    public function testAmbiguousClassAndSequenceDiagram(): void
    {
        // This diagram has indicators for both class and sequence diagrams
        // but not enough clear indicators for either
        $uml = <<<'UML'
@startuml
entity User
entity Order
User --> Order
Order --> User
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_UNKNOWN, $type);
    }

    public function testStrongClassDiagramIndicators(): void
    {
        // This has multiple high-weight class indicators
        $uml = <<<'UML'
@startuml
interface Printable {
    +print(): void
}

abstract class Document {
    #content: string
}

class PDF extends Document implements Printable {
    -format: string
    +print(): void
}

enum DocumentType {
    PDF
    WORD
    TEXT
}
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_CLASS, $type);
    }

    public function testStrongSequenceDiagramIndicators(): void
    {
        // This has multiple high-weight sequence indicators
        $uml = <<<'UML'
@startuml
participant "Client" as C
participant "Server" as S
boundary "API Gateway" as G
control "Controller" as Ctrl
database "Database" as DB

activate C
C ->> G: Request
activate G
G ->> Ctrl: Process
deactivate G
activate Ctrl
Ctrl ->> DB: Query
activate DB
DB -->> Ctrl: Result
deactivate DB
Ctrl -->> C: Response
deactivate Ctrl
deactivate C
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_SEQUENCE, $type);
    }

    public function testMinimalValidActivityDiagram(): void
    {
        // Tests if minimal but valid activity diagram is detected
        $uml = <<<'UML'
@startuml
start
if (condition) then
    :action 1;
else
    :action 2;
endif
stop
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_ACTIVITY, $type);
    }

    public function testComplexStateDiagram(): void
    {
        // Tests state diagram with multiple indicators
        $uml = <<<'UML'
@startuml
[*] --> State1
state State1 {
    [*] --> SubState1
    SubState1 --> SubState2
    SubState2 --> [*]
}
State1 --> State2
State2 --> [*]
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_STATE, $type);
    }

    public function testDiagramWithComments(): void
    {
        // Tests that comments don't affect type detection
        $uml = <<<'UML'
@startuml
' This is a class diagram
' with lots of comments
' including keywords from other diagrams like:
' participant, actor, start, stop

class User {
    ' another comment
    +id: int
    ' participant keyword in comment
    +name: string
}

' actor keyword in comment
class Account {
    ' start keyword in comment
    -balance: float
}

' sequence diagram arrow in comment -->
User --> Account
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_CLASS, $type);
    }

    public function testEmptyDiagram(): void
    {
        $uml = <<<'UML'
@startuml

@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_UNKNOWN, $type);
    }

    public function testDetectSequenceDiagram(): void
    {
        $uml = <<<'UML'
@startuml
actor User
participant "Web App" as App
participant "API Server" as API
database Database

User -> App: Login
App -> API: Authenticate
API -> Database: Check credentials
Database --> API: Return user data
API --> App: Authentication token
App --> User: Display dashboard
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_SEQUENCE, $type);
    }

    public function testDetectActivityDiagram(): void
    {
        $uml = <<<'UML'
@startuml
start
if (Is user logged in?) then (yes)
  :Display dashboard;
else (no)
  :Show login form;
  if (Valid credentials?) then (yes)
    :Authenticate user;
  else (no)
    :Show error message;
  endif
endif
stop
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_ACTIVITY, $type);
    }

    public function testExplicitDiagramType(): void
    {
        $uml = <<<'UML'
@startuml(class)
' This is a class diagram with explicit type
class User
class Order
User -- Order
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_CLASS, $type);
    }

    public function testUnknownDiagramType(): void
    {
        $uml = <<<'UML'
@startuml
' This has no clear indicators
title "Some diagram"
@enduml
UML;

        $type = $this->detector->detectType($uml);
        $this->assertEquals(DiagramTypeDetector::TYPE_UNKNOWN, $type);
    }
}
