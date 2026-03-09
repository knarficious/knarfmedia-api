<?php

namespace App\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UpdatePublicationFileAction extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    public function __invoke(Request $request, int $id)
    {
        $publication = $this->em->getRepository(Publication::class)->find($id);
        
        if (!$publication) {
            throw new NotFoundHttpException('Publication non trouvée!');
        }
        
        $uploadedFile = $request->files->get('file');
        
        if (null === $uploadedFile || !$uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new BadRequestHttpException('Le champ "file" est requis et doit être un fichier valide.');
        }
        
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestHttpException('Erreur lors de l\'upload du fichier : ' . $uploadedFile->getErrorMessage());
        }

        try {
            $publication->setFile($uploadedFile); // ← if Vich: this moves/copies file
            
            // If using VichUploaderBundle + updatedAt pattern:
            // $publication->setUpdatedAt(new \DateTimeImmutable());
            
            $this->em->flush();
        } catch (\Exception $e) {
            // Log if needed: $this->logger->error(...);
            throw new BadRequestHttpException('Échec de la mise à jour du fichier : ' . $e->getMessage());
        }
        
        return new Response(
            json_encode(['status' => 'success', 'message' => 'Fichier mis à jour']),
            201,
            ['Content-Type' => 'application/json']
            );
    }
}