<?php

namespace App\Listeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessListener
{
    private $cookieSecure = false;

    public function __construct(protected $jwtTokenTTL)
    {}

    /**
     * This function is responsible for the authentication part
     *
     * @param AuthenticationSuccessEvent $event
     * @return JWTAuthenticationSuccessResponse
     */
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): JWTAuthenticationSuccessResponse
    {
        /** @var JWTAuthenticationSuccessResponse $response */
        $response = $event->getResponse();
        $data = $event->getData();
        $tokenJWT = $data['token'];
        unset($data['token']);
        unset($data['refresh_token']);
        $event->setData($data);

        $response->headers->
            setCookie(
            new Cookie(
                'BEARER',
                $tokenJWT,
                (new \DateTime())
                    ->add(new \DateInterval('PT' . $this->jwtTokenTTL . 'S')),
                '/',
                null,
                $this->cookieSecure
                )
            );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}