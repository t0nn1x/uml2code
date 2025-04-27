<?php

namespace App\Repository;

use App\Entity\OAuthConnection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthConnection>
 *
 * @method OAuthConnection|null find($id, $lockMode = null, $lockVersion = null)
 * @method OAuthConnection|null findOneBy(array $criteria, array $orderBy = null)
 * @method OAuthConnection[]    findAll()
 * @method OAuthConnection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OAuthConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthConnection::class);
    }

    public function save(OAuthConnection $entity, bool $flush = false): void
    {
        $entity->setUpdatedAt(new \DateTime());
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OAuthConnection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find a connection by provider and provider user ID
     */
    public function findOneByProviderAndUserId(string $provider, string $providerUserId): ?OAuthConnection
    {
        return $this->findOneBy([
            'provider' => $provider,
            'providerUserId' => $providerUserId,
        ]);
    }

    /**
     * Create or update an OAuth connection
     */
    public function createOrUpdateConnection(
        User $user,
        string $provider,
        string $providerUserId,
        string $accessToken,
        ?string $refreshToken = null,
        ?\DateTimeInterface $expiresAt = null
    ): OAuthConnection {
        $connection = $this->findOneByProviderAndUserId($provider, $providerUserId);

        if (!$connection) {
            $connection = new OAuthConnection();
            $connection->setUser($user);
            $connection->setProvider($provider);
            $connection->setProviderUserId($providerUserId);
        }

        $connection->setAccessToken($accessToken);
        $connection->setRefreshToken($refreshToken);
        $connection->setExpiresAt($expiresAt);

        $this->save($connection, true);

        return $connection;
    }
}
