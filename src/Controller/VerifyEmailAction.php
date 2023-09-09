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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[AsController]
#[Route(
    path: '/verify',
    name: 'app_verify_email',
    methods: ['GET'],
//     defaults: [
//         '_api_resource_class' => User::class,
//         '_api_operation_name' => '_api_/verify'
//     ]
    )]
final class VerifyEmailAction extends AbstractController
{
    //private EmailVerifier $emailVerifier;
    
    public function __construct(private EmailVerifier $emailVerifier)
    {
        //$this->emailVerifier = $emailVerifier;
    }
    
    public function __invoke(Request $request, UserRepository $userRepository): RedirectResponse
    {
        $userId = $request->query->get('email');

        $user = $userRepository->findOneBy(['email' => $userId]);        

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);  
            
        } catch (VerifyEmailExceptionInterface $exception) {            
            
            $response = new RedirectResponse($this->generateUrl('api_doc'));
            $response->setContent($exception->getReason());
            $response->headers->set('Content-Type', 'text/html');
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            
            return $response;
        }
        
        return $this->redirectToRoute('app_email_validation', [], Response::HTTP_MOVED_PERMANENTLY);
     }
     
     
}