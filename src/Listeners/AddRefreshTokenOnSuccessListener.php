<?php
namespace App\Listeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AddRefreshTokenOnSuccessListener
{
    private RefreshTokenGeneratorInterface $refreshTokenManager;
    private int $ttl;

    public function __construct(RefreshTokenGeneratorInterface $refreshTokenManager, int $ttl = 2592000)
    {
        $this->refreshTokenManager = $refreshTokenManager;
        $this->ttl = $ttl;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        // Création du refresh token (le bundle crée et persiste)
        $refreshToken = $this->refreshTokenManager->createForUserWithTtl($user, $this->ttl);

        // Ajout à la réponse JSON
        $data['refresh_token'] = $refreshToken->getRefreshToken();
        $event->setData($data);
    }
}
