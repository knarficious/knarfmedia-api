<?php

namespace App\Controller;

use App\Entity\Publication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class PostController extends AbstractController
{
    public function __invoke(Request $request): Publication
    {
        $data = json_decode($request->getContent(), true);
        
        $post = new Publication();
        
        $post->setTitle($data['title']);
        $post->setSummary($data['summary']);
        $post->setAuthor($this->getUser());
        $post->setContent($data['content']);
        $post->setPublishedAt(new \DateTime());
        
        return $post;
    }
}