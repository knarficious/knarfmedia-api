<?php

namespace App\Controller;

use App\Entity\Publication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class PublicationUpdateController extends AbstractController
{
    public function __invoke(Request $request): Publication
    {
//         $data = json_decode($request->getContent(), true);
        
//         $post = new Publication();
        
//         $post->setTitle($data['title']);
//         $post->setSummary($data['summary']);
//         $post->setAuthor($this->getUser());
//         $post->setContent($data['content']);
//         $post->setPublishedAt(new \DateTime());
        
//         return $post;

        
            
            $post = $request->attributes->get('data');
            if (!($post instanceof Publication))
            {
                throw new \RuntimeException('L\'objet n\'est pas une instance de Post');
            }
            
            $uploadedFile = $request->files->get('file');
            //         if (!$uploadedFile) {
            //             throw new BadRequestHttpException('"file" is required');
            //         }
            
            $post->setFile($uploadedFile);
            //$post->setUpdatedAt(new \DateTime());
            $post->setAuthor($this->getUser());
            $post->setTitle($request->attributes->get('data')->getTitle());
            $post->setSummary($request->attributes->get('data')->getSummary());
            $post->setContent($request->attributes->get('data')->getContent());
            $tags = $request->attributes->get('data')->getTags();
            if (count($tags) !== 0) {
                
                foreach ($tags as $tag){
                    
                    $post->addTag($tag);
                };
            }
            
            return $post;
        
    }
}