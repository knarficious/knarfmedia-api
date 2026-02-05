<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use App\Repository\PublicationRepository;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[AsController]
final class PostCommentController extends AbstractController
{
    #[Route('/publications/{publicationId}/commenter', name: 'api_publication_comment_create', methods: ['POST'])]
    public function __invoke(
        Request $request,
        int $publicationId,
        PublicationRepository $repository,
        EntityManagerInterface $em
        ): Comment 
        {
            $post = $repository->find($publicationId);
            if (!$post) throw $this->createNotFoundException('Publication not found');
            
            $data = json_decode($request->getContent(), true);
            if (empty($data['content'])) {
                throw new \InvalidArgumentException('Le contenu du commentaire est obligatoire.');
            }
            
            $comment = new Comment();
            $comment->setContent($data['content']);
            $comment->setPublication($post);
            $comment->setAuthor($this->getUser());
            $comment->setPublishedAt(new \DateTimeImmutable());
            
            $em->persist($comment);
            $em->flush();
            
            return $comment;
    }
}

