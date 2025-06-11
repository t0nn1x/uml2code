<?php

namespace App\Controller\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;

class SecurityController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method should not be called directly.');
    }

    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        try {
            $this->logger->info('OAuth Google: Starting Google OAuth flow');
            
            $client = $clientRegistry->getClient('google');
            $this->logger->info('OAuth Google: Client retrieved successfully');
            
            $redirectResponse = $client->redirect([
                'email',
                'profile'
            ], []);
            
            $this->logger->info('OAuth Google: Redirect URL generated', [
                'url' => $redirectResponse->getTargetUrl()
            ]);
            
            return $redirectResponse;
        } catch (\Exception $e) {
            $this->logger->error('OAuth Google: Error in connectGoogle', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'OAuth Google Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    public function connectGoogleCheck(Request $request): RedirectResponse
    {
        try {
            $this->logger->info('OAuth Google: Check callback started');
            
            // This is handled by the OAuthAuthenticator
            // Get locale from session or default
            $locale = $request->getSession()->get('_locale', 'uk');
            
            $this->logger->info('OAuth Google: Redirecting to dashboard', ['locale' => $locale]);
            
            return $this->redirectToRoute('app_dashboard', ['_locale' => $locale]);
        } catch (\Exception $e) {
            $this->logger->error('OAuth Google: Error in connectGoogleCheck', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'OAuth Google Check Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    public function connectGithub(ClientRegistry $clientRegistry): RedirectResponse
    {
        try {
            $this->logger->info('OAuth GitHub: Starting GitHub OAuth flow');
            
            $client = $clientRegistry->getClient('github');
            $this->logger->info('OAuth GitHub: Client retrieved successfully');
            
            $redirectResponse = $client->redirect([
                'user:email',
                'read:user'
            ], []);
            
            $this->logger->info('OAuth GitHub: Redirect URL generated', [
                'url' => $redirectResponse->getTargetUrl()
            ]);
            
            return $redirectResponse;
        } catch (\Exception $e) {
            $this->logger->error('OAuth GitHub: Error in connectGithub', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'OAuth GitHub Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    public function connectGithubCheck(Request $request): RedirectResponse
    {
        try {
            $this->logger->info('OAuth GitHub: Check callback started');
            
            // This is handled by the OAuthAuthenticator
            // Get locale from session or default
            $locale = $request->getSession()->get('_locale', 'uk');
            
            $this->logger->info('OAuth GitHub: Redirecting to dashboard', ['locale' => $locale]);
            
            return $this->redirectToRoute('app_dashboard', ['_locale' => $locale]);
        } catch (\Exception $e) {
            $this->logger->error('OAuth GitHub: Error in connectGithubCheck', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'OAuth GitHub Check Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
