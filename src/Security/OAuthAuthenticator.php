<?php

namespace App\Security;

use App\Entity\OAuthConnection;
use App\Entity\User;
use App\Repository\OAuthConnectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private const OAUTH_PROVIDERS = ['google', 'github'];

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly ManagerRegistry $doctrine,
        private readonly UserRepository $userRepository,
        private readonly OAuthConnectionRepository $oauthConnectionRepository
    ) {}

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManager();
    }

    private function safeFlush(EntityManagerInterface $em): void
    {
        try {
            $em->flush();
        } catch (\Exception $e) {
            // If flush fails, try to reset the EntityManager and flush again
            $this->doctrine->resetManager();
            $newEm = $this->doctrine->getManager();
            $newEm->flush();
        }
    }

    public function supports(Request $request): ?bool
    {
        foreach (self::OAUTH_PROVIDERS as $provider) {
            // Check if the current route matches any of our OAuth check routes
            if ($request->attributes->get('_route') === 'connect_' . $provider . '_check') {
                return true;
            }
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $route = $request->attributes->get('_route');
        $provider = str_replace('connect_', '', str_replace('_check', '', $route));

        if (!in_array($provider, self::OAUTH_PROVIDERS)) {
            throw new AuthenticationException('Invalid OAuth provider');
        }

        $client = $this->clientRegistry->getClient($provider);
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $provider) {
                try {
                    $oauthUser = $client->fetchUserFromToken($accessToken);
                    /** @var ResourceOwnerInterface $oauthUser */

                    // Get user data
                    $userId = $oauthUser->getId();
                    $userData = $oauthUser->toArray();
                    $email = $userData['email'] ?? null;

                    if (!$email) {
                        throw new AuthenticationException('No email provided by OAuth provider');
                    }

                    // For different providers, we might need to get user data in different ways
                    $firstName = null;
                    $lastName = null;

                    if ($provider === 'google') {
                        $firstName = $userData['given_name'] ?? null;
                        $lastName = $userData['family_name'] ?? null;
                    } elseif ($provider === 'github') {
                        // GitHub API doesn't split names, it just provides a name field if available
                        $name = $userData['name'] ?? '';
                        if ($name) {
                            $nameParts = explode(' ', $name, 2);
                            $firstName = $nameParts[0] ?? null;
                            $lastName = $nameParts[1] ?? null;
                        }
                    }

                    $em = $this->getEntityManager();

                    // Check if we already have this OAuth connection
                    $existingConnection = $this->oauthConnectionRepository->findOneByProviderAndUserId($provider, $userId);

                    if ($existingConnection) {
                        // User exists, update the token
                        $existingConnection->setAccessToken($accessToken->getToken());

                        if ($accessToken->getRefreshToken()) {
                            $existingConnection->setRefreshToken($accessToken->getRefreshToken());
                        }

                        if ($accessToken->getExpires()) {
                            $expiresAt = new \DateTime();
                            $expiresAt->setTimestamp($accessToken->getExpires());
                            $existingConnection->setExpiresAt($expiresAt);
                        }

                        $this->safeFlush($em);

                        return $existingConnection->getUser();
                    }

                    // No existing connection, create a new user or connect to existing user
                    $user = $this->userRepository->findOrCreateFromOauth($email, $firstName, $lastName);

                    // Create the OAuth connection
                    $connection = new OAuthConnection();
                    $connection->setUser($user);
                    $connection->setProvider($provider);
                    $connection->setProviderUserId($userId);
                    $connection->setAccessToken($accessToken->getToken());

                    if ($accessToken->getRefreshToken()) {
                        $connection->setRefreshToken($accessToken->getRefreshToken());
                    }

                    if ($accessToken->getExpires()) {
                        $expiresAt = new \DateTime();
                        $expiresAt->setTimestamp($accessToken->getExpires());
                        $connection->setExpiresAt($expiresAt);
                    }

                    $em->persist($connection);
                    $this->safeFlush($em);

                    return $user;
                } catch (\Exception $e) {
                    throw new AuthenticationException('OAuth authentication failed: ' . $e->getMessage());
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        try {
            // Update last login timestamp
            $user = $token->getUser();
            if ($user instanceof User) {
                $em = $this->getEntityManager();
                $user->setLastLoginAt(new \DateTime());
                $this->safeFlush($em);
            }
        } catch (\Exception $e) {
            // Don't fail the login if we can't update the timestamp
            // Just log it or ignore it
        }

        // Get locale from session or default
        $locale = $request->getSession()->get('_locale', 'uk');

        // Redirect to dashboard after successful login
        return new RedirectResponse($this->router->generate('app_dashboard', ['_locale' => $locale]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This is required for implementing AuthenticationEntryPointInterface.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Get locale from request or default
        $locale = $request->getLocale();

        return new RedirectResponse(
            $this->router->generate('app_login', ['_locale' => $locale]),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
