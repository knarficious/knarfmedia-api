<?php

namespace App\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\PostCommentController;
#[ApiResource(operations: [new Get(), new Put(), new Delete(), new GetCollection(), new Post()], denormalizationContext: ['groups' => ['write']], mercure: true, normalizationContext: ['groups' => ['comment:read']])]
#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read', 'write'])]
    private $post;
    #[ORM\Column(type: 'text')]
    #[Groups(['write', 'comment:read'])]
    private $content;
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['comment:read', 'write'])]
    private $publishedAt;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read', 'write'])]
    private $author;
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getPost() : ?Publication
    {
        return $this->post;
    }
    public function setPost(?Publication $post) : self
    {
        $this->post = $post;
        return $this;
    }
    public function getContent() : ?string
    {
        return $this->content;
    }
    public function setContent(string $content) : self
    {
        $this->content = $content;
        return $this;
    }
    public function getPublishedAt() : ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }
    public function setPublishedAt(\DateTimeImmutable $publishedAt) : self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }
    public function getAuthor() : ?User
    {
        return $this->author;
    }
    public function setAuthor(?User $author) : self
    {
        $this->author = $author;
        return $this;
    }
}
