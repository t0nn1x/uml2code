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
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private ActionHistoryRepository $actionHistoryRepository,
        private SystemLogRepository $systemLogRepository,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        private RouterInterface $router
    ) {
    }

    #[Route('/admin-panel', name: 'admin')]
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
            ->setTitle($this->translator->trans('dashboard.title', [], 'admin'))
            ->setFaviconPath('favicon.ico')
            ->setDefaultColorScheme('dark')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard($this->translator->trans('nav.dashboard', [], 'admin'), 'fa fa-home');
        
        yield MenuItem::section($this->translator->trans('user.title', [], 'admin'));
        yield MenuItem::linkToCrud($this->translator->trans('nav.users', [], 'admin'), 'fas fa-users', User::class);
        
        yield MenuItem::section($this->translator->trans('system_logs.title', [], 'admin'));
        yield MenuItem::linkToCrud($this->translator->trans('nav.action_history', [], 'admin'), 'fas fa-history', ActionHistory::class);
        yield MenuItem::linkToCrud($this->translator->trans('nav.system_logs', [], 'admin'), 'fas fa-file-alt', SystemLog::class);
        yield MenuItem::linkToRoute($this->translator->trans('nav.log_management', [], 'admin'), 'fas fa-cogs', 'admin_logs_management');
        
        yield MenuItem::section($this->translator->trans('language.switcher', [], 'admin'));
        $currentRequest = $this->requestStack->getCurrentRequest();
        $currentRoute = $currentRequest->attributes->get('_route');
        $routeParams = $currentRequest->attributes->get('_route_params');
        
        yield MenuItem::linkToUrl('🇬🇧 ' . $this->translator->trans('language.english', [], 'admin'), 'fas fa-language', 
            $this->router->generate($currentRoute, array_merge($routeParams, ['_locale' => 'en']))
        );
        yield MenuItem::linkToUrl('🇺🇦 ' . $this->translator->trans('language.ukrainian', [], 'admin'), 'fas fa-language', 
            $this->router->generate($currentRoute, array_merge($routeParams, ['_locale' => 'uk']))
        );
        
        yield MenuItem::section($this->translator->trans('nav.dashboard', [], 'admin'));
        yield MenuItem::linkToRoute($this->translator->trans('nav.back_to_site', [], 'admin'), 'fas fa-arrow-left', 'app_dashboard', ['_locale' => $this->container->get('request_stack')->getCurrentRequest()->getLocale()]);
        yield MenuItem::linkToLogout($this->translator->trans('nav.logout', [], 'admin'), 'fas fa-sign-out-alt');
    }
} 
