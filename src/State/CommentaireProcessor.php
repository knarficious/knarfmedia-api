<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;

use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Publication;
use App\Entity\Comment;
use ApiPlatform\Metadata\Post;

class CommentaireProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private EntityManagerInterface $em
        ) {}
    
    public function process(mixed $comment, Operation $operation, array $uriVariables = [], array $context = []): Comment
    {
        if ($comment->getId() === null && $operation instanceof Post) {
            $publicationId = $uriVariables["publicationId"] ?? null;
            $publication = $this->em->getRepository(Publication::class)->find($publicationId);
            $publication->addComment($comment);            
        }
        
        $comment->setAuthor($this->security->getUser());
        
        return $this->persistProcessor->process($comment, $operation, $uriVariables, $context);
    }
}
