<?php
namespace App\Listeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        $user = $event->getUser();
        $payload['username'] = $user->getUsername();
        $payload['id'] = $user->getId();
        $event->setData($payload);
    }
}

