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
         
         $publication = $publicationRepository->find($request->attributes->get('id'));
         
         if (!($publication instanceof Publication))
         {
             throw new \RuntimeException('L\'objet n\'est pas une instance de Post');
         }
         
         if (array_key_exists("title", $data)) {
             $title = $data["title"];             
             $publication->setTitle($title);
         }
         if (array_key_exists("summary", $data)) {
             $summary = $data["summary"];             
             $publication->setSummary($summary);
         }
         if (array_key_exists("content", $data)) {
             $content = $data["content"];             
             $publication->setContent($content);
         }
         if (array_key_exists("tags", $data) && count($data['tags']) > 0) {
             $tags = $data["tags"];             
             
             foreach ($tags as $tag){
                 $tagReference = 'App\Entity\Tag';
                 $tagClass = $em->getReference($tagReference, $tag["id"]);
                 
                 if (!$em->contains($tagClass)) {
                     $publication->addTag($tagClass);
                     $em->refresh($publication);
                 }
                 $tagObjects = $tagRepository->findBy(['id' => $tag["id"]]);
                 foreach ($tagObjects as $object) {
                     
                     $object->addPublication($publication);
                     $em->refresh($publication);
                 }
             }
         }
         
         if (array_key_exists("tags", $data) && count($data['tags']) == 0) {
             ;
         }
         
         $em->flush();
         
         $publication->setUpdatedAt(new \DateTimeImmutable());   
            
         return $publication;
        
    }
}