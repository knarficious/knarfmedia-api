<?php

namespace App\Controller;

use App\Entity\MediaObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Entity\Publication;

#[AsController]
class CreateMediaObjectAction extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager){      
    }
    
    public function __invoke(Request $request): MediaObject
    {
        $uploadedFiles = $request->files->all('files'); // Récupère files[]
        
        if (empty($uploadedFiles)) {
            // Fallback pour compatibilité avec l'ancien nom "file" (un seul fichier)
            $singleFile = $request->files->get('file');
            if ($singleFile) {
                $uploadedFiles = [$singleFile];
            } else {
                throw new BadRequestHttpException('No files provided. Use "files[]" or "file".');
            }
        }
        
        $mediaObjects = [];
        $publicationId = explode('=', explode(';', $request->request->get('publicationId'))[0])[1];
        $publication = $this->entityManager->find(Publication::class, $publicationId) ;
        
        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile->isValid()) {
                throw new BadRequestHttpException('Invalid file: ' . $uploadedFile->getErrorMessage());
            }
            
            $this->validateFileManually($uploadedFile);
            
            $mediaObject = new MediaObject();
            $mediaObject->setFile($uploadedFile);
            $mediaObject->setPublication($publication);
            
            $mediaObjects[] = $mediaObject;
        }
        
        // Si un seul fichier est envoyé, on retourne directement l'objet (compatibilité API Platform)
        // Sinon on retourne un tableau (API Platform gère les collections)
        return count($mediaObjects) === 1 ? $mediaObjects[0] : $mediaObjects;
    }
    
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
}