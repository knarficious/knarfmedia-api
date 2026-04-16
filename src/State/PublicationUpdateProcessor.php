<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Tag;

final class PublicationUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        ) {}
        
        public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
        {
            if (!$data instanceof Publication) {
                return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            }            
            
            $user = $this->security->getUser();
            if (!$user) {
                throw new AccessDeniedException('Vous devez être connecté.');
            }
            
            if ($data->getAuthor() && $data->getAuthor() !== $user && !$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedException('Vous ne pouvez modifier que vos propres publications.');
            }
            
            $data->setAuthor($user);
            
            /** @var Request $request */
            $request = $context['request'] ?? null;
            
            if ($request) {
                $body = $request->getContent();
                if ($body) {
                    $patchData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    
                    if (isset($patchData['title'])) {
                        $data->setTitle($patchData['title']);
                    }
                    if (isset($patchData['summary'])) {
                        $data->setSummary($patchData['summary']);
                    }
                    
                    // === CORRECTION CONTENT ===
                    if (isset($patchData['content'])) {
                        $newContent = $patchData['content'];
                        
                        // Assure le format {type: "doc", content: [...]}
                        if (is_array($newContent) && (!isset($newContent['type']) || $newContent['type'] !== 'doc')) {
                            $newContent = [
                                'type' => 'doc',
                                'content' => $newContent['content'] ?? $newContent
                            ];
                        }
                        
                        $data->setContent($newContent);
                    }                    
                }
                
                if (isset($patchData['tags']) && is_array($patchData['tags'])) {
                    $newTagIris = array_map(fn($tag) => is_string($tag) ? $tag : ($tag['@id'] ?? ''), $patchData['tags']);
                    $newTagIris = array_filter($newTagIris);
                    
                    $currentTags = $data->getTags();
                    
                    // 1. Supprimer les tags qui ne sont plus dans la nouvelle liste
                    foreach ($currentTags as $tag) {
                        $tagIri = $tag->getId() ? '/tags/' . $tag->getId() : '';
                        if (!in_array($tagIri, $newTagIris, true)) {
                            $data->removeTag($tag);
                            dump("Tag supprimé : " . $tag->getName());
                        }
                    }
                    
                    // 2. Ajouter les nouveaux tags
                    foreach ($newTagIris as $iri) {
                        $tagId = (int) substr($iri, strrpos($iri, '/') + 1);
                        $tag = $this->entityManager->getRepository(Tag::class)->find($tagId);
                        
                        if ($tag && !$data->getTags()->contains($tag)) {
                            $data->addTag($tag);
                            dump("Tag ajouté : " . $tag->getName());
                        }
                    }
                }
                
                // Suppression des fichiers
                if (isset($patchData['files'] )) {
                    $filesToRemove = $patchData['files'] ?? $request->request->all('files');
                    if (!empty($filesToRemove)) {
                        foreach ((array)$filesToRemove as $iri) {
                            if (!is_string($iri) || !str_starts_with($iri, '/media_objects/')) {
                                continue;
                            }
                            
                            $id = (int) substr($iri, strrpos($iri, '/') + 1);
                            $mediaObject = $this->entityManager->getRepository(MediaObject::class)->find($id);
                            
                            if ($mediaObject && $mediaObject->getPublication() === $data) {
                                $data->removeFile($mediaObject);
                                $this->entityManager->remove($mediaObject);
                                dump("Fichier supprimé : " . $iri);
                            }
                        }
                    }
                }

            }
            
            $data->setUpdatedAt(new \DateTimeImmutable());
            
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }
}