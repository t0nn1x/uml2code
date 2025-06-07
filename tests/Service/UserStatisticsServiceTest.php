<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserStatistics;
use App\Service\UserStatisticsService;
use App\Repository\UserStatisticsRepository;
use App\Repository\ActionHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UserStatisticsServiceTest extends TestCase
{
    private UserStatisticsService $service;
    private UserStatisticsRepository&MockObject $statisticsRepository;
    private ActionHistoryRepository&MockObject $historyRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private LoggerInterface&MockObject $logger;
    private User $testUser;

    protected function setUp(): void
    {
        $this->statisticsRepository = $this->createMock(UserStatisticsRepository::class);
        $this->historyRepository = $this->createMock(ActionHistoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserStatisticsService(
            $this->statisticsRepository,
            $this->historyRepository,
            $this->entityManager,
            $this->logger
        );

        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setFirstName('Test');
        $this->testUser->setLastName('User');
    }

    public function testUpdateStatistics(): void
    {
        $mockStatistics = $this->createMock(UserStatistics::class);
        
        $this->statisticsRepository
            ->expects($this->once())
            ->method('updateStatistics')
            ->with(
                $this->testUser,
                'generate',
                3,
                150,
                'PHP'
            )
            ->willReturn($mockStatistics);

        $this->service->updateStatistics(
            $this->testUser,
            'generate',
            3,
            150,
            'PHP'
        );
    }

    public function testGetSummaryStatistics(): void
    {
        $expectedStats = [
            'diagrams_processed' => 25,
            'files_generated' => 75,
            'lines_of_code' => 2500,
            'total_actions' => 100,
            'breakdown' => [
                'parse' => 25,
                'convert' => 35,
                'generate' => 40
            ]
        ];

        $this->statisticsRepository
            ->expects($this->once())
            ->method('getComprehensiveStatistics')
            ->with($this->testUser)
            ->willReturn($expectedStats);

        $result = $this->service->getSummaryStatistics($this->testUser);

        $this->assertEquals($expectedStats, $result);
        $this->assertEquals(25, $result['diagrams_processed']);
        $this->assertEquals(75, $result['files_generated']);
        $this->assertEquals(2500, $result['lines_of_code']);
        $this->assertEquals(100, $result['total_actions']);
    }

    public function testGetLanguageStatistics(): void
    {
        $expectedLanguages = [
            [
                'language' => 'PHP',
                'count' => 15,
                'totalLines' => 1200
            ],
            [
                'language' => 'Java',
                'count' => 10,
                'totalLines' => 800
            ],
            [
                'language' => 'Python',
                'count' => 5,
                'totalLines' => 300
            ]
        ];

        $this->statisticsRepository
            ->expects($this->once())
            ->method('getLanguageStatistics')
            ->with($this->testUser)
            ->willReturn($expectedLanguages);

        $result = $this->service->getLanguageStatistics($this->testUser);

        $this->assertEquals($expectedLanguages, $result);
        $this->assertCount(3, $result);
        $this->assertEquals('PHP', $result[0]['language']);
        $this->assertEquals(15, $result[0]['count']);
    }
} 
