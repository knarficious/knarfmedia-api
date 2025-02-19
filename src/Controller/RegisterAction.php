<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsController]
final class RegisterAction extends AbstractController 
{
    
    public function __invoke(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface): User
    {
        $user = new User();
        
        $data = json_decode($request->getContent(), true);
        
        $user->setUsername($data['username']);
        $user->setPassword($userPasswordHasherInterface->hashPassword($user, $data['plainPassword']));
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);        

        return $user;        
    }
}

