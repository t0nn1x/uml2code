<?php

namespace App\Tests\Core\Parser\ClassDiagram\Application\Service;

use App\Core\Parser\ClassDiagram\Application\Service\ClassDiagramParserInterface;
use App\Core\Parser\ClassDiagram\Application\Service\UmlParserService;
use App\Core\Parser\ClassDiagram\Domain\Exception\ParserException;
use App\Core\Parser\ClassDiagram\Domain\Model\ClassDiagram;
use App\Core\Parser\ClassDiagram\Domain\Model\ClassElement;
use App\Core\Parser\ClassDiagram\Domain\Model\Relationship;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UmlParserServiceTest extends TestCase
{
    /**
     * @var ClassDiagramParserInterface&MockObject
     */
    private $mockParser;
    
    private UmlParserService $umlParserService;
    
    protected function setUp(): void
    {
        $this->mockParser = $this->createMock(ClassDiagramParserInterface::class);
        $this->umlParserService = new UmlParserService($this->mockParser);
    }
    
    public function testParseUml(): void
    {
        // Arrange
        $umlContent = '@startuml
title "E-commerce Domain Model"

package "Domain Model" {
    abstract class Product {
        -id: UUID
        -name: string
        -price: Money
        -description: string
        +getId(): UUID
        +getName(): string
        +getPrice(): Money
        +setPrice(price: Money): void
    }

    class PhysicalProduct extends Product {
        -weight: float
        -dimensions: Dimensions
        +calculateShippingCost(): Money
    }

    class DigitalProduct extends Product {
        -downloadUrl: string
        -fileSize: int
        +generateDownloadLink(): string
    }

    class Order {
        -id: UUID
        -items: List<OrderItem>
        -status: OrderStatus
        -customer: Customer
        +addItem(item: OrderItem): void
        +removeItem(item: OrderItem): void
        +calculateTotal(): Money
    }
}
@enduml';
        $expectedDiagram = new ClassDiagram();
        $expectedDiagram->setTitle('E-commerce Domain Model');
        
        $this->mockParser->expects($this->once())
            ->method('parse')
            ->with($umlContent)
            ->willReturn($expectedDiagram);
        
        // Act
        $actualDiagram = $this->umlParserService->parseUml($umlContent);
        
        // Assert
        $this->assertSame($expectedDiagram, $actualDiagram);
    }
    
    public function testDiagramToArray(): void
    {
        // Arrange
        $diagram = new ClassDiagram();
        $diagram->setTitle('Test Diagram');
        
        /** @var ClassElement&MockObject $class */
        $class = $this->createMock(ClassElement::class);
        $class->expects($this->once())
            ->method('toArray')
            ->willReturn(['name' => 'User']);
        $diagram->addClass($class);
        
        /** @var Relationship&MockObject $relationship */
        $relationship = $this->createMock(Relationship::class);
        $relationship->expects($this->once())
            ->method('toArray')
            ->willReturn(['source' => 'User', 'target' => 'Role', 'type' => 'association']);
        $diagram->addRelationship($relationship);
        
        $expectedArray = [
            'title' => 'Test Diagram',
            'classes' => [['name' => 'User']],
            'relationships' => [['source' => 'User', 'target' => 'Role', 'type' => 'association']]
        ];
        
        // Act
        $actualArray = $this->umlParserService->diagramToArray($diagram);
        
        // Assert
        $this->assertEquals($expectedArray, $actualArray);
    }
    
    public function testParseUmlToArray(): void
    {
        // Arrange
        $umlContent = '@startuml
title "Authentication System"

package "Security" {
    interface AuthenticationProvider {
        +authenticate(credentials: Credentials): User
        +supports(credentials: Credentials): bool
    }

    class JwtAuthProvider implements AuthenticationProvider {
        -secretKey: string
        -tokenTtl: Duration
        +generateToken(user: User): string
        +validateToken(token: string): bool
    }

    class OAuth2Provider implements AuthenticationProvider {
        -clientId: string
        -clientSecret: string
        -providers: List<OAuthProvider>
        +handleCallback(code: string): User
    }

    class User {
        -id: UUID
        -email: string
        -roles: Set<Role>
        +hasRole(role: Role): bool
    }

    enum Role {
        ADMIN
        USER
        GUEST
    }
}
@enduml';
        $diagram = new ClassDiagram();
        $diagram->setTitle('Authentication System');
        
        /** @var ClassElement&MockObject $class */
        $class = $this->createMock(ClassElement::class);
        $class->expects($this->once())
            ->method('toArray')
            ->willReturn(['name' => 'User']);
        $diagram->addClass($class);
        
        $expectedArray = [
            'title' => 'Authentication System',
            'classes' => [['name' => 'User']],
            'relationships' => []
        ];
        
        $this->mockParser->expects($this->once())
            ->method('parse')
            ->with($umlContent)
            ->willReturn($diagram);
        
        // Act
        $actualArray = $this->umlParserService->parseUmlToArray($umlContent);
        
        // Assert
        $this->assertEquals($expectedArray, $actualArray);
    }
    
    public function testValidateSyntax(): void
    {
        // Arrange
        $umlContent = '@startuml
title "Task Management System"

package "Domain" {
    interface TaskRepository {
        +findById(id: UUID): Task
        +save(task: Task): void
        +delete(task: Task): void
        +findByAssignee(user: User): List<Task>
    }

    class Task {
        -id: UUID
        -title: string
        -description: string
        -status: TaskStatus
        -assignee: User
        -dueDate: DateTime
        +assignTo(user: User): void
        +updateStatus(status: TaskStatus): void
    }

    enum TaskStatus {
        TODO
        IN_PROGRESS
        REVIEW
        DONE
    }

    class TaskBoard {
        -id: UUID
        -name: string
        -columns: List<TaskColumn>
        +moveTask(task: Task, column: TaskColumn): void
    }
}
@enduml';
        
        $this->mockParser->expects($this->once())
            ->method('validate')
            ->with($umlContent)
            ->willReturn(true);
        
        // Act
        $result = $this->umlParserService->validateSyntax($umlContent);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testExtractMetadata(): void
    {
        // Arrange
        $umlContent = '@startuml
title "Event Sourcing System"

package "EventSourcing" {
    interface EventStore {
        +append(stream: string, events: List<Event>): void
        +read(stream: string): EventStream
    }

    class Event {
        -id: UUID
        -type: string
        -data: array
        -metadata: array
        -timestamp: DateTime
        +serialize(): string
        +deserialize(data: string): Event
    }

    class EventStream {
        -events: List<Event>
        -version: int
        +append(event: Event): void
        +replay(): void
    }

    class Aggregate {
        -id: UUID
        -version: int
        #changes: List<Event>
        +getUncommittedChanges(): List<Event>
        +markChangesAsCommitted(): void
        #apply(event: Event): void
    }
}

note right of EventStore
    Handles persistence and
    retrieval of event streams
end note
@enduml';
        
        $expectedMetadata = [
            'title' => 'Event Sourcing System',
            'classCount' => 4,
            'relationshipCount' => 0,
            'packages' => ['EventSourcing'],
            'notes' => 1
        ];
        
        $this->mockParser->expects($this->once())
            ->method('extractMetadata')
            ->with($umlContent)
            ->willReturn($expectedMetadata);
        
        // Act
        $actualMetadata = $this->umlParserService->extractMetadata($umlContent);
        
        // Assert
        $this->assertEquals($expectedMetadata, $actualMetadata);
    }
    
    public function testCleanDiagramArray(): void
    {
        // Arrange
        $diagramArray = [
            'classes' => [
                ['name' => 'User', 'attributes' => []],
                ['name' => 'User', 'attributes' => []],  // Duplicate
                ['name' => 'Role', 'attributes' => [
                    ['name' => 'users', 'type' => 'Collection', 'typeArguments' => ['User']]
                ]],
            ],
            'relationships' => [
                ['source' => 'User', 'target' => 'Role', 'type' => 'association'],
                ['source' => 'User', 'target' => 'Role', 'type' => 'association'],  // Duplicate
                ['source' => 'Role', 'target' => 'Permission', 'type' => 'composition'],
            ]
        ];
        
        $expectedCleanedArray = [
            'classes' => [
                ['name' => 'User', 'attributes' => []],
                ['name' => 'Role', 'attributes' => [
                    ['name' => 'users', 'type' => 'Collection<User>', 'typeArguments' => ['User']]
                ]],
            ],
            'relationships' => [
                ['source' => 'User', 'target' => 'Role', 'type' => 'association'],
                ['source' => 'Role', 'target' => 'Permission', 'type' => 'composition'],
            ]
        ];
        
        // Act
        $actualCleanedArray = $this->umlParserService->cleanDiagramArray($diagramArray);
        
        // Assert
        $this->assertEquals($expectedCleanedArray, $actualCleanedArray);
    }
    
    public function testConstructorWithDefaultParser(): void
    {
        // Arrange & Act
        $service = new UmlParserService();
        
        // Assert
        $this->assertInstanceOf(UmlParserService::class, $service);
        // We can't directly test the default parser as it's private, but we can ensure the service works
        
        // This is more of an integration test and would require actual UML content to test
        // For unit testing purposes, we're only verifying the class instantiates correctly
    }
    
    public function testParseUmlWithException(): void
    {
        // Arrange
        $umlContent = 'invalid UML content';
        
        $this->mockParser->expects($this->once())
            ->method('parse')
            ->with($umlContent)
            ->willThrowException(new ParserException('Invalid UML syntax'));
        
        // Assert & Act
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Invalid UML syntax');
        
        $this->umlParserService->parseUml($umlContent);
    }
} 
