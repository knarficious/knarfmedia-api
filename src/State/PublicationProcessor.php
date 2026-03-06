<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RequestStack;

final class PublicationProcessor implements ProcessorInterface
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
            throw new \RuntimeException('Expected Publication instance');
        }
        
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('No request available');
        }
        
        // Get the uploaded file (multipart/form-data part named "file")
        $uploadedFile = $request->files->get('file');
        
        if ($uploadedFile instanceof UploadedFile) {
            if (!$uploadedFile->isValid()) {
                throw new BadRequestHttpException('Invalid file upload: ' . $uploadedFile->getErrorMessage());
            }
            
            // Optional: manual checks before Vich (size, mime) if you want to fail earlier
            $this->validateFileManually($uploadedFile);
            
            $data->setFile($uploadedFile);
        }
        
        // Author is set via security (or here if needed)
        $user = $this->getUser(); // Assuming you have a security service or trait
        if (!$user) {
            throw new AccessDeniedException('User must be authenticated.');
        }
        $data->setAuthor($user);
        
        // Tags should already be denormalized by API Platform (if sent as IRIs)
        // If not working, you can loop here as fallback, but usually not needed
        
        // Set dates if not already (your constructor does it, but to be sure)
        if (null === $data->getPublishedAt()) {
            $data->setPublishedAt(new \DateTimeImmutable());
        }
        $data->setUpdatedAt(new \DateTimeImmutable());
        
        foreach ($data->getTags() as $tag) {
            if (!$tag->getPublications()->contains($data)) {
                $tag->addPublication($data);
            }
        }
        
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
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new BadRequestHttpException('File too large (max 10MB).');
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