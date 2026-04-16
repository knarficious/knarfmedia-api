<?php

// src/Controller/RegisterAction.php
namespace App\Controller;

use App\DTO\RegisterUserInput;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use ApiPlatform\Validator\Exception\ValidationException;

#[AsController]
final class RegisterAction
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SerializerInterface $serializer // optionnel si tu veux désérialiser auto
        ) {
    }
    
    public function __invoke(Request $request): User
    {
        $contentType = $request->headers->get('Content-Type', '');
        
        if (str_contains($contentType, 'application/ld+json')) {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException('Invalid JSON: ' . json_last_error_msg());
            }
        } else {
            $data = $request->request->all();
        }
        
        // Option 1 : désérialisation manuelle + validation (recommandé)
        $input = new RegisterUserInput();
        $input->username      = $data['username']      ?? null;
        $input->email         = $data['email']         ?? null;
        $input->plainPassword = $data['plainPassword'] ?? null;
        
        $violations = $this->validator->validate($input);
        
        if (count($violations) > 0) {
            // API Platform gère automatiquement la sérialisation en ConstraintViolationList
            // Mais ici comme c'est custom, on peut throw une exception que le normalizer capte
            // Ou retourner directement un Response 422 (mais throw est mieux)
            throw new ValidationException($violations);
        }
        
        // Si tout est OK
        $user = new User();
        $user->setUsername($input->username);
        $user->setEmail($input->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->plainPassword));
        $user->setRoles(['ROLE_USER']);
        
        return $user;
    }
}