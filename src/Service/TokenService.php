<?php

namespace App\Service;

use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

class TokenService
{
    private RefreshTokenManagerInterface $refreshTokenManager;
    
    public function __construct(RefreshTokenManagerInterface $refreshTokenManager)
    {
        $this->refreshTokenManager = $refreshTokenManager;
    }
    
    public function createRefreshTokenForUser(UserInterface $user, int $ttlInSeconds = 2592000): RefreshToken
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setRefreshToken(bin2hex(random_bytes(32)));
        $refreshToken->setValid(new \DateTimeImmutable("+{$ttlInSeconds} seconds"));
        
        $this->refreshTokenManager->save($refreshToken);
        
        return $refreshToken;
    }
    
    public function invalidateRefreshToken(RefreshToken $refreshToken): void
    {
        $this->refreshTokenManager->delete($refreshToken);
    }
}


