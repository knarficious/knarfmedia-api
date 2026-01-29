<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Link;
use ApiPlatform\State\CreateProvider;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use App\State\CommentaireProcessor; 
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\PostCommentController;

#[ApiResource(

operations: [
    new Get(),
    new GetCollection(),
    new GetCollection(
        uriTemplate: '/users/{userId}/comments',
        uriVariables: [
            'userId' => new Link(fromClass: User::class, toProperty: 'author')
        ]
        ),
    new GetCollection(
        uriTemplate: '/publications/{publicationId}/comments',
        uriVariables: [
            'publicationId' => new Link(fromClass: Publication::class, toProperty: 'publication')
        ],
        ), 
    new Post(
        uriTemplate: '/publications/{publicationId}/commenter',
        uriVariables: [
            'publicationId' => new Link(fromClass: Publication::class, toProperty: 'publication')
        ],
        security: "is_granted('ROLE_USER')",
        provider: CreateProvider::class,
        processor: CommentaireProcessor::class
        //controller: PostCommentController::class
        ),
    new Put(security: "object.getAuthor() == user"),
    new Patch(security: "object.getAuthor() == user"),
    new Delete(security: "is_granted('ROLE_ADMIN') or object.getAuthor() == user")
],
denormalizationContext: ['groups' => ['comment:write']], 
mercure: true, 
normalizationContext: ['groups' => ['comment:read']]
)]

#[ApiFilter(SearchFilter::class, properties: [
    'post' => 'exact',
    'parent' => 'exact'
])]
#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ApiProperty(identifier: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['comment:read'])]
    private ?int $id = null;
    
    #[ApiProperty]
    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read', 'publication:update  '])]
    private ?Publication $publication = null;    
    
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Groups(['comment:write', 'comment:read'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    private ?self $parent = null;    
    
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[MaxDepth(2)]
    #[Groups(['comment:write', 'comment:read'])]
    private Collection $children;
    
    #[ORM\Column(type: 'text')]
    #[Groups(['comment:write', 'comment:read', 'publication:read'])]
    private string $content;
    
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['comment:read', 'publication:read'])]
    private \DateTimeImmutable $publishedAt;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:read', 'publication:read'])]
    #[Link(toProperty: 'author')]
    private User $author;
    
    public function __construct() {
        $this->children = new ArrayCollection();
        $this->publishedAt = new \DateTimeImmutable();
    }
    
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getPublication() : ?Publication
    {
        return $this->publication;
    }
    public function setPublication(?Publication $publication) : self
    {
        $this->publication = $publication;
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
    /**
     * @return \App\Entity\Comment
     */
    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    /**
     * @param \App\Entity\Comment $parent
     */
    public function setParent($parent): self
    {
        $this->parent = $parent;
        return $this;
    }

}
