<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use App\Repository\PublicationRepository;

#[AsController]
final class PostCommentController extends AbstractController
{
    public function __invoke(Request $request, PublicationRepository $repository)
    {
       $data = json_decode($request->getContent(), true); 
       
       $comment = new Comment();
       $comment->setContent($data['content']);
       $comment->setPublishedAt(new \DateTimeImmutable());
       $comment->setAuthor($this->getUser());
       $post = $repository->findOneBy(['id' => $request->get('id')]);
       $comment->setPost($post);
       $post->addComment($comment);
       
       return $comment;
    }
}

