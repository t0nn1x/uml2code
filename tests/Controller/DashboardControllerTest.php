<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\ActionHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ActionHistoryService $historyService;
    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->historyService = $container->get(ActionHistoryService::class);
        
        // Create test user
        $this->testUser = new User();
        $this->testUser->setEmail('dashboard-test@example.com');
        $this->testUser->setFirstName('Dashboard');
        $this->testUser->setLastName('Test');
        $this->testUser->setIsVerified(true);
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->entityManager->remove($this->testUser);
        $this->entityManager->flush();
        
        parent::tearDown();
    }

    public function testDashboardPageLoads(): void
    {
        $this->client->loginUser($this->testUser);
        
        $this->client->request('GET', '/dashboard');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome');
        $this->assertSelectorExists('#diagrams-count');
        $this->assertSelectorExists('#files-count');
        $this->assertSelectorExists('#lines-count');
        $this->assertSelectorExists('#total-actions-count');
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/dashboard');
        
        $this->assertResponseRedirects();
    }

    public function testSummaryApiEndpoint(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create some test history data
        $files = [
            ['filename' => 'User.php', 'content' => '<?php class User {}']
        ];
        $metadata = [
            'programmingLanguage' => 'PHP',
            'generatorVersion' => '1.0',
            'totalLinesOfCode' => 50
        ];
        
        $this->historyService->record(
            $this->testUser,
            'generate',
            $files,
            'ClassDiagram',
            $metadata
        );
        
        $this->client->request('GET', '/api/dashboard/summary');
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('stats', $response);
        $this->assertArrayHasKey('diagrams_processed', $response['stats']);
        $this->assertArrayHasKey('files_generated', $response['stats']);
        $this->assertArrayHasKey('lines_of_code', $response['stats']);
        $this->assertArrayHasKey('total_actions', $response['stats']);
        $this->assertArrayHasKey('breakdown', $response['stats']);
    }

    public function testActivityApiEndpoint(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create test activity
        $files = [
            ['filename' => 'Product.php', 'content' => '<?php class Product {}']
        ];
        $metadata = [
            'programmingLanguage' => 'PHP',
            'diagramName' => 'E-commerce System',
            'totalLinesOfCode' => 75
        ];
        
        $this->historyService->record(
            $this->testUser,
            'convert',
            $files,
            'ClassDiagram',
            $metadata
        );
        
        $this->client->request('GET', '/api/dashboard/activity?limit=5');
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('activity', $response);
        $this->assertIsArray($response['activity']);
        
        if (!empty($response['activity'])) {
            $activity = $response['activity'][0];
            $this->assertArrayHasKey('id', $activity);
            $this->assertArrayHasKey('actionType', $activity);
            $this->assertArrayHasKey('programmingLanguage', $activity);
            $this->assertArrayHasKey('totalLinesOfCode', $activity);
            $this->assertArrayHasKey('diagramName', $activity);
        }
    }

    public function testTrendsApiEndpoint(): void
    {
        $this->client->loginUser($this->testUser);
        
        $this->client->request('GET', '/api/dashboard/trends?days=7');
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('trends', $response);
        $this->assertIsArray($response['trends']);
    }

    public function testLanguagesApiEndpoint(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create test data with different languages
        $files = [['filename' => 'Test.java', 'content' => 'public class Test {}']];
        $metadata = ['programmingLanguage' => 'Java'];
        
        $this->historyService->record(
            $this->testUser,
            'generate',
            $files,
            'ClassDiagram',
            $metadata
        );
        
        $this->client->request('GET', '/api/dashboard/languages');
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('languages', $response);
        $this->assertIsArray($response['languages']);
    }

    public function testApiEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            '/api/dashboard/summary',
            '/api/dashboard/activity',
            '/api/dashboard/trends',
            '/api/dashboard/languages'
        ];
        
        foreach ($endpoints as $endpoint) {
            $this->client->request('GET', $endpoint);
            $this->assertResponseRedirects();
        }
    }

    public function testActivityApiWithLimitParameter(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create multiple activities
        for ($i = 0; $i < 10; $i++) {
            $files = [['filename' => "Test{$i}.php", 'content' => "<?php class Test{$i} {}"]];
            $this->historyService->record(
                $this->testUser,
                'parse',
                $files,
                'ClassDiagram'
            );
        }
        
        $this->client->request('GET', '/api/dashboard/activity?limit=3');
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertCount(3, $response['activity']);
    }

    public function testSummaryStatsCalculation(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create test data with known values
        $files1 = [['filename' => 'User.php', 'content' => "<?php\nclass User {\n    // content\n}"]];
        $metadata1 = [
            'programmingLanguage' => 'PHP',
            'totalLinesOfCode' => 4
        ];
        
        $files2 = [['filename' => 'Product.php', 'content' => "<?php\nclass Product {\n    // content\n    // more content\n}"]];
        $metadata2 = [
            'programmingLanguage' => 'PHP',
            'totalLinesOfCode' => 5
        ];
        
        $this->historyService->record($this->testUser, 'generate', $files1, 'ClassDiagram', $metadata1);
        $this->historyService->record($this->testUser, 'convert', $files2, 'ClassDiagram', $metadata2);
        
        $this->client->request('GET', '/api/dashboard/summary');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals(2, $response['stats']['files_generated']);
        $this->assertEquals(9, $response['stats']['lines_of_code']);
        $this->assertEquals(2, $response['stats']['total_actions']);
    }

    public function testLanguageStatsCalculation(): void
    {
        $this->client->loginUser($this->testUser);
        
        // Create activities with different languages
        $phpFiles = [['filename' => 'User.php', 'content' => '<?php class User {}']];
        $javaFiles = [['filename' => 'User.java', 'content' => 'public class User {}']];
        
        $this->historyService->record(
            $this->testUser,
            'generate',
            $phpFiles,
            'ClassDiagram',
            ['programmingLanguage' => 'PHP', 'totalLinesOfCode' => 10]
        );
        
        $this->historyService->record(
            $this->testUser,
            'generate',
            $phpFiles,
            'ClassDiagram',
            ['programmingLanguage' => 'PHP', 'totalLinesOfCode' => 15]
        );
        
        $this->historyService->record(
            $this->testUser,
            'generate',
            $javaFiles,
            'ClassDiagram',
            ['programmingLanguage' => 'Java', 'totalLinesOfCode' => 20]
        );
        
        $this->client->request('GET', '/api/dashboard/languages');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $languages = $response['languages'];
        
        // Check PHP stats
        $phpStats = array_filter($languages, fn($lang) => $lang['language'] === 'PHP');
        $phpStats = array_values($phpStats)[0] ?? null;
        $this->assertNotNull($phpStats);
        $this->assertEquals(2, $phpStats['count']);
        $this->assertEquals(25, $phpStats['totalLines']);
        
        // Check Java stats
        $javaStats = array_filter($languages, fn($lang) => $lang['language'] === 'Java');
        $javaStats = array_values($javaStats)[0] ?? null;
        $this->assertNotNull($javaStats);
        $this->assertEquals(1, $javaStats['count']);
        $this->assertEquals(20, $javaStats['totalLines']);
    }

    public function testEmptyDashboardState(): void
    {
        $this->client->loginUser($this->testUser);
        
        $this->client->request('GET', '/api/dashboard/summary');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals(0, $response['stats']['diagrams_processed']);
        $this->assertEquals(0, $response['stats']['files_generated']);
        $this->assertEquals(0, $response['stats']['lines_of_code']);
        $this->assertEquals(0, $response['stats']['total_actions']);
    }

    public function testUserIsolation(): void
    {
        // Create another user
        $otherUser = new User();
        $otherUser->setEmail('other-user@example.com');
        $otherUser->setFirstName('Other');
        $otherUser->setLastName('User');
        $otherUser->setIsVerified(true);
        
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();
        
        // Create activity for other user
        $files = [['filename' => 'Other.php', 'content' => '<?php class Other {}']];
        $this->historyService->record($otherUser, 'generate', $files, 'ClassDiagram');
        
        // Login as test user and check they don't see other user's data
        $this->client->loginUser($this->testUser);
        
        $this->client->request('GET', '/api/dashboard/summary');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals(0, $response['stats']['total_actions']);
        
        // Clean up
        $this->entityManager->remove($otherUser);
        $this->entityManager->flush();
    }
} 
