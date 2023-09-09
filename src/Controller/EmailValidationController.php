<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailValidationController extends AbstractController
{
    #[Route('/email/validation', name: 'app_email_validation')]
    public function index(): Response
    {
        return $this->render('registration/email_validation.html.twig', [
            'controller_name' => 'EmailValidationController',
        ]);
    }
}
