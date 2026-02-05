<?php

namespace App\Controller;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Entity\Publication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tag;

#[AsController]
final class PostController extends AbstractController
{
    public function __invoke(Request $request, EntityManagerInterface $em): Publication
    {
        $user = $this->getUser(); // from Security
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté.');
        }
        $publication = $request->attributes->get('data');
        if (!($publication instanceof Publication))
        {
            throw new \RuntimeException('L\'objet n\'est pas une instance de Publication');
        }
        
        $uploadedFile = $request->files->get('file');
//         if (!$uploadedFile) {
//             throw new BadRequestHttpException('"file" is required');
//         }
        
        $publication->setFile($uploadedFile);
        //$post->setUpdatedAt(new \DateTime()); 
        $publication->setAuthor($user);
        $publication->setTitle($request->attributes->get('data')->getTitle());
        $publication->setSummary($request->attributes->get('data')->getSummary());
        $publication->setContent($request->attributes->get('data')->getContent());
        $tags = $request->attributes->get('data')->getTags();
            
            foreach ($tags as $tag){
                $tagReference = 'App\Entity\Tag';
                $tagClass = $em->getReference($tagReference, $tag->getId());
                
                if ($em->contains($tagClass)) {
                    $publication->addTag($tagClass);
                }
               }
        $em->persist($publication);
        $em->flush();
        
        return $publication;
    }
}