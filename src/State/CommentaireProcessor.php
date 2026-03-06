<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Publication;
use App\Entity\Comment;
use ApiPlatform\Metadata\Post;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CommentaireProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private EntityManagerInterface $em
        ) {
    }
    
    public function process(mixed $comment, Operation $operation, array $uriVariables = [], array $context = []): Comment
    {
        if (!$operation instanceof Post) {
            return $this->persistProcessor->process($comment, $operation, $uriVariables, $context);
        }
        
        // Récupérer la publication (obligatoire)
        $publicationId = $uriVariables['publicationId'] ?? null;
        if (!$publicationId) {
            throw new NotFoundHttpException('Publication ID manquant dans l’URI');
        }
        
        $publication = $this->em->getRepository(Publication::class)->find($publicationId);
        if (!$publication) {
            throw new NotFoundHttpException('Publication non trouvée');
        }
        
        // Lier le commentaire à la publication
        $publication->addComment($comment);
        
        // Parent optionnel
        if (isset($uriVariables['commentId'])) {
            $commentId = $uriVariables['commentId'];
            $parent = $this->em->getRepository(Comment::class)->find($commentId);
            if (!$parent) {
                throw new NotFoundHttpException('Commentaire parent non trouvé');
            }
            $comment->setParent($parent);
        }
        
        // Auteur = utilisateur connecté
        $user = $this->security->getUser();
        if (!$user) {
            throw new \RuntimeException('Utilisateur non authentifié');
        }
        $comment->setAuthor($user);
        
        // Persistance finale via le processor par défaut
        return $this->persistProcessor->process($comment, $operation, $uriVariables, $context);
    }
}