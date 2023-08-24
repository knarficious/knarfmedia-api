<?php
// api/src/Controller/CreateMediaObjectAction.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Entity\Publication;

#[AsController]
final class PostImageController extends AbstractController
{
    public function __invoke(Request $request)
    {
        $post = $request->attributes->get('data');
        if (!($post instanceof Publication))
        {
            throw new \RuntimeException('L\'objet n\'est pas une instance de Post');
        }
        
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }
        
        $post->setFile($request->files->get('file'));
        $post->setUpdatedAt(new \DateTime());        

        
        return $post;
    }
}