<?php

namespace App\Controller\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
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
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email',
                'profile'
            ], []);
    }

    public function connectGoogleCheck(Request $request): RedirectResponse
    {
        // This is handled by the OAuthAuthenticator
        // Get locale from session or default
        $locale = $request->getSession()->get('_locale', 'uk');
        return $this->redirectToRoute('app_dashboard', ['_locale' => $locale]);
    }

    public function connectGithub(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect([
                'user:email',
                'read:user'
            ], []);
    }

    public function connectGithubCheck(Request $request): RedirectResponse
    {
        // This is handled by the OAuthAuthenticator
        // Get locale from session or default
        $locale = $request->getSession()->get('_locale', 'uk');
        return $this->redirectToRoute('app_dashboard', ['_locale' => $locale]);
    }
}
