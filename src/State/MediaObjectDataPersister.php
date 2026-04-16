<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MediaObjectDataPersister implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager
        ) {
    }
    
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $request = $context['request'] ?? null;
        
        // Cas d'un seul MediaObject
        if ($data instanceof MediaObject) {
            $this->validateFileManually($data);
            $this->linkToPublication($data, $request);
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }
        
        // Cas de plusieurs MediaObject (tableau retourné par le controller)
        if (is_iterable($data)) {
            $results = [];
            
            foreach ($data as $mediaObject) {
                if (!$mediaObject instanceof MediaObject) {
                    continue;
                }
                
                $this->validateFileManually($mediaObject);
                $this->linkToPublication($mediaObject, $request);
                $results[] = $this->persistProcessor->process($mediaObject, $operation, $uriVariables, $context);
            }
            
            return $results; // Retourne un tableau de MediaObject créés
        }
        
        // Fallback pour les autres cas
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
    
    // Optional: helper for extra validation
    private function validateFileManually(UploadedFile $file): void
    {
        // Example: enforce mime & size manually if Assert\File timing is still problematic
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'audio/mpeg', 'audio/ogg', 'audio/wav', 'video/mp4', 'video/webm', 'video/ogg'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            throw new BadRequestHttpException('Invalid file type.');
        }
        if ($file->getSize() > 20 * 1024 * 1024) {
            throw new BadRequestHttpException('File too large (max 20MB).');
        }
    }
    
    private function linkToPublication(MediaObject $mediaObject, ?Request $request): void
    {
        if (!$request) {
            return;
        }
        
        $publicationIri = $request->request->get('publication'); // "/publications/42"
        
        if ($publicationIri) {
            $publication = $this->getPublicationFromIri($publicationIri);
            
            if (!$publication) {
                throw new NotFoundHttpException('Publication not found for IRI: ' . $publicationIri);
            }
            
            $mediaObject->setPublication($publication);
            $publication->addFile($mediaObject); // Maintient la relation bidirectionnelle
        }
    }
    
    private function getPublicationFromIri(string $iri): ?Publication
    {
        if (preg_match('#/publications/(\d+)$#', $iri, $matches)) {
            return $this->entityManager->getRepository(Publication::class)->find((int)$matches[1]);
        }
        
        if (is_numeric($iri)) {
            return $this->entityManager->getRepository(Publication::class)->find((int)$iri);
        }
        
        throw new BadRequestHttpException('Invalid publication format. Send IRI like "/publications/42"');
    }
}