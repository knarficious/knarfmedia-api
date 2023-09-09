<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsController]
final class RegisterAction extends AbstractController 
{
    private EmailVerifier $emailVerifier;
    
    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }
    
    public function __invoke(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface): User
    {
        $user = new User();
        
        $data = json_decode($request->getContent(), true);
        
        $user->setUsername($data['username']);
        $user->setPassword($userPasswordHasherInterface->hashPassword($user, $data['plainPassword']));
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);
        
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
            ->from(new Address('leknarf@hotmail.com', 'Knarf Media admin'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context(['user' => $user])
            );
        return $user;
        
    }   
  

//     #[Route(
//         path: '/verify',
//         name: 'app_verify_email',
//         methods: ['GET'],
//         defaults: [
//             '_api_resource_class' => User::class,
//             '_api_operation_name' => '_api_/verify'
//         ]
//         )]
//     public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
//     {
//         $userId = $request->get('userId');
        
//         if(null === $userId)
//         {
//             return new JsonResponse(['data' => 'id not found'], 404);
//         }
        
//         $user = $userRepository->findOneBy(['username' => $userId]);
        
//         if (null === $user)
//         {
//             return new JsonResponse(['data' => 'id not found'], 404);
//         }
        
//         // validate email confirmation link, sets User::isVerified=true and persists
//         try {
//             $this->emailVerifier->handleEmailConfirmation($request, $user);
//         } catch (VerifyEmailExceptionInterface $exception) {
//             //$this->addFlash('verify_email_error', $exception->getReason());
            
//             return new JsonResponse(['data' => $exception->getReason()], 500);
//         }        
        
//         return new JsonResponse(['data' => 'compte'.$user->getEmail().'confirmé']);
//     }

}

