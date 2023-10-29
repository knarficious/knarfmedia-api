<?php

namespace App\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Repository\PublicationRepository;
use App\Repository\TagRepository;

#[AsController]
final class PublicationUpdateController extends AbstractController
{
    public function __invoke(Request $request, PublicationRepository $publicationRepository, TagRepository $tagRepository, EntityManagerInterface $em): Publication
    {
         $data = json_decode($request->getContent(), true);
         
         $post = $publicationRepository->find($request->attributes->get('id'));
         
         if (array_key_exists("title", $data)) {
             $title = $data["title"];             
             $post->setTitle($title);
         }
         if (array_key_exists("summary", $data)) {
             $summary = $data["summary"];             
             $post->setSummary($summary);
         }
         if (array_key_exists("content", $data)) {
             $content = $data["content"];             
             $post->setContent($content);
         }
         if (array_key_exists("tags", $data)) {
             $tags = $data["tags"];
         }
         
         
        
         
        
//         $post->setTitle($data['title']);
//         $post->setSummary($data['summary']);
//         $post->setAuthor($this->getUser());
//         $post->setContent($data['content']);
//         $post->setPublishedAt(new \DateTime());
        
//         return $post;

        
            
            
            if (!($post instanceof Publication))
            {
                throw new \RuntimeException('L\'objet n\'est pas une instance de Post');
            }
            
           //$uploadedFile = $request->files->get('file');
            //         if (!$uploadedFile) {
            //             throw new BadRequestHttpException('"file" is required');
            //         }
            
            //$post->setFile($uploadedFile);
            $post->setUpdatedAt(new \DateTime());
            $post->setAuthor($this->getUser());
            if (count($tags) !== 0) {
                foreach ($tags as $tag){
                    $tagReference = 'App\Entity\Tag';
                    $tagClass = $em->getReference($tagReference, $tag["id"]);
                    
                    if (!$em->contains($tagClass)) {
                        $post->addTag($tagClass);
                        $em->refresh($post);
                        $em->flush();
                    }
                    $tagObjects = $tagRepository->findBy(['id' => $tag["id"]]);
                    foreach ($tagObjects as $object) {
                        
                        $object->addPublication($post);
                        $em->refresh($post);
                        $em->flush();
                    }

                    
                    
                }   
            }
            
            return $post;
        
    }
}