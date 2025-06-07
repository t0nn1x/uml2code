<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\ActionHistory;
use App\Entity\SystemLog;
use App\Repository\UserRepository;
use App\Repository\ActionHistoryRepository;
use App\Repository\SystemLogRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private ActionHistoryRepository $actionHistoryRepository,
        private SystemLogRepository $systemLogRepository
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $this->logger->info('Admin dashboard accessed', [
            'user' => $this->getUser()?->getUserIdentifier(),
            'ip' => $request?->getClientIp()
        ]);

        // Get dashboard statistics
        $userCount = $this->userRepository->count([]);
        $actionCount = $this->actionHistoryRepository->count([]);
        $errorLogs = $this->systemLogRepository->findErrorLogs(10);
        
        // Get active users in last 24 hours
        $yesterday = new \DateTime('-24 hours');
        $activeUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.lastLoginAt >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getResult();
        
        // Get recent actions
        $recentActions = $this->actionHistoryRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        return $this->render('admin/dashboard.html.twig', [
            'user_count' => $userCount,
            'action_count' => $actionCount,
            'error_logs' => count($errorLogs),
            'active_users' => count($activeUsers),
            'recent_actions' => $recentActions,
            'recent_errors' => array_slice($errorLogs, 0, 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('UML2Code Admin Panel')
            ->setFaviconPath('favicon.ico')
            ->setDefaultColorScheme('dark')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('User Management');
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        
        yield MenuItem::section('System Logs');
        yield MenuItem::linkToCrud('Action History', 'fas fa-history', ActionHistory::class);
        yield MenuItem::linkToCrud('System Logs', 'fas fa-file-alt', SystemLog::class);
        yield MenuItem::linkToRoute('Log Management', 'fas fa-cogs', 'admin_logs_management');
        
        yield MenuItem::section('Application');
        yield MenuItem::linkToRoute('Back to Site', 'fas fa-arrow-left', 'app_dashboard');
        yield MenuItem::linkToLogout('Logout', 'fas fa-sign-out-alt');
    }
} 
