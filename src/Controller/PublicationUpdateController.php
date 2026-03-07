<?php

namespace App\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\PublicationRepository;
use App\Repository\TagRepository;

#[AsController]
final class PublicationUpdateController extends AbstractController
{
    public function __invoke(
        Request $request,
        PublicationRepository $publicationRepository,
        TagRepository $tagRepository,
        EntityManagerInterface $em
        ): Publication {
            $id = $request->attributes->get('id');
            $publication = $publicationRepository->find($id);
            
            if (!$publication instanceof Publication) {
                throw new NotFoundHttpException('Publication not found');
            }
            
            $data = json_decode($request->getContent(), true) ?? [];
            
            // Apply simple fields only if present and actually different (optional check)
            if (array_key_exists('title', $data)) {
                $publication->setTitle($data['title']);
            }
            if (array_key_exists('summary', $data)) {
                $publication->setSummary($data['summary']);
            }
            if (array_key_exists('content', $data)) {
                $publication->setContent($data['content']);
            }
            
            // Tags handling – much safer & cleaner
            if (array_key_exists('tags', $data)) {
                $newTagIds = array_column($data['tags'], 'id'); // extract IDs from sent array
                
                // Remove all existing tags first (clear association)
                foreach ($publication->getTags() as $existingTag) {
                    $publication->removeTag($existingTag);
                    $existingTag->removePublication($publication);
                }
                
                // Add new ones
                if (!empty($newTagIds)) {
                    $tags = $tagRepository->findBy(['id' => $newTagIds]);
                    
                    foreach ($tags as $tag) {
                        $publication->addTag($tag);
                        $tag->addPublication($publication);
                    }
                }
            }
            
            // Update timestamp
            $publication->setUpdatedAt(new \DateTimeImmutable());
            
            // One single flush at the end
            $em->flush();
            
            return $publication;
    }
}