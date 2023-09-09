<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsController]
#[Route(
    path: '/verify',
    name: 'app_verify_email',
    methods: ['GET'],
    defaults: [
        '_api_resource_class' => User::class,
        '_api_operation_name' => '_api_/verify'
    ]
    )]
final class VerifyEmailAction extends AbstractController
{
    //private EmailVerifier $emailVerifier;
    
    public function __construct(private EmailVerifier $emailVerifier)
    {
        //$this->emailVerifier = $emailVerifier;
    }
    
    public function __invoke(Request $request, User $user): User
    {
//         $userId = $request->get('userId');        

//         $user = $userRepository->findOneBy(['username' => $userId]);        

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request);
        } catch (VerifyEmailExceptionInterface $exception) {
            //$this->addFlash('verify_email_error', $exception->getReason());
            
            return $this->json(['data' => $exception->getReason()]);
        }
        
        return $user;
     }
}