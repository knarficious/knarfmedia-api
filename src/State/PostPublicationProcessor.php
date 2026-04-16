<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RequestStack;

final class PostPublicationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly RequestStack $requestStack
        ) {
    }
    
    /**
     * @param Publication $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {        
        if (!$data instanceof Publication) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }
        
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('No request available');
        }
        
        // Récupérer le JSON du contenu Tiptap
        $contentJson = $request->request->get('content');
        if ($contentJson) {
            try {
                $contentArray = json_decode($contentJson, true, 512, JSON_THROW_ON_ERROR);
                $data->setContent($contentArray);
            } catch (\JsonException $e) {
                throw new BadRequestHttpException('Invalid JSON in content field: ' . $e->getMessage());
            }
        }
        
//         if ($request->request->all('tags')) {
//             $tags = $request->request->all('tags');
            
//             foreach ($tags as $tag) {
//                 $data->addTag($tag);
//             }
//         }
        
        // Récupérer les fichiers uploadés
        $uploadedFiles = $request->files->all('files');
        
        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                
                $this->validateFileManually($uploadedFile);
              
                // Ici tu peux créer les MediaObject ou les lier directement
                $mediaObject = new MediaObject();
                $mediaObject->setFile($uploadedFile);
                $mediaObject->setPublication($data);
                $data->addFile($mediaObject);
            }
        }
        
        // Author is set via security (or here if needed)
        $user = $this->getUser(); // Assuming you have a security service or trait
        if (!$user) {
            throw new AccessDeniedException('User must be authenticated.');
        }
        $data->setAuthor($user); 
        
        $data->generateSlug();
        
        // Set dates if not already (your constructor does it, but to be sure)
        if (null === $data->getPublishedAt()) {
            $data->setPublishedAt(new \DateTimeImmutable());
        }
        $data->setUpdatedAt(new \DateTimeImmutable());
        
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
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

    
    // If you use Symfony's security component and have AbstractController-like getUser()
    // Otherwise inject Security service
    private function getUser()
    {
        // Adjust according to your setup (e.g. inject Security $security and return $security->getUser())
        return $this->security->getUser(); // example if you inject it
    }
}