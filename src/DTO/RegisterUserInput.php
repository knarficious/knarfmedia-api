<?php 
// src/DTO/RegisterUserInput.php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank(message: "Le nom d'utilisateur est requis")]
    #[Assert\Length(min: 3, max: 50, minMessage: "Au moins 3 caractères")]
    public ?string $username = null;
    
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "Format d'email invalide")]
    public ?string $email = null;
    
    #[Assert\NotBlank(message: "Le mot de passe est requis")]
    #[Assert\Length(min: 8, minMessage: "Au moins 8 caractères")]
    // Tu peux ajouter plus : #[Assert\Regex(...)] etc.
    public ?string $plainPassword = null;
}