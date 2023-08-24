<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
final class PostCommentController extends AbstractController
{
    public function __invoke(Request $request)
    {
       $data = $request->attributes->get('data');
       
       if(!$data instanceof Comment)
       {
           throw new \RuntimeException('L\'objet n\'est pas une instance de Comment');
       }
       
       //dd($data);
       
       $comment = new Comment();
       $comment->setContent($data->getContent());
       $comment->setPublishedAt(new \DateTimeImmutable());
       $comment->setAuthor($this->getUser());
       $comment->setPost($data->getPost());
       
       return $data;
    }
}

