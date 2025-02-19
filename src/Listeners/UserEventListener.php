<?php

namespace App\Listeners;


use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class UserEventListener
{
    public function __construct(private EmailVerifier $emailVerifier){}
    
    public function postPersist(LifecycleEventArgs $args): void
    {
        $user = $args->getObject();
        
        if (!($user instanceof User)) {
            return;
        }
        
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
            ->from(new Address('no-reply@jaurinformatique.fr', 'Knarf Media'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context(['user' => $user])
            );
        
    }
}

