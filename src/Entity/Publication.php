<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Metadata\Link;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Controller\PostController;
use App\Controller\PublicationUpdateController;
use App\Controller\PostCommentController;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[Vich\Uploadable]
#[ApiResource(
operations: [
    new Get(),
    new GetCollection(),
    new Post(
        security: "is_granted('ROLE_USER')",
        controller: PostController::class,
        inputFormats: ['multipart' => ['multipart/form-data']]
        ),
    new Put(
        security: "is_granted('ROLE_ADMIN') or object.author == user",
        inputFormats: ['multipart' => ['multipart/form-data']],
        controller: PublicationUpdateController::class,
        ),
    new Patch(security: "is_granted('ROLE_ADMIN') or object.author == user"),
    new Delete(security: "is_granted('ROLE_ADMIN') or object.author == user"),
],
normalizationContext: ['groups' => ['read', 'read:Publication', 'tag:read']],
denormalizationContext: ['groups' => ['post:image', 'post:create', 'post:update' ]], 
)]
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[UniqueEntity("title")]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id = null;
    
    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['read', 'post:create', 'post:update', 'tag:read'])]
    private $title = null;
    
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['read', 'post:create', 'post:update'])]
    private $summary = null;
    
    #[ORM\Column(type: 'text')]
    #[Groups(['read', 'post:create', 'post:update'])]
    private $content = null;
    
    #[ApiProperty(iris: ['https://schema.org/dateCreated'])]
    #[ORM\Column(type: 'date')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['read'])]
    private ?\DateTimeInterface $publishedAt = null;
    
    #[ApiProperty(iris: ['https://schema.org/dateModified'])]
    #[ORM\Column(type: 'date')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['read'])]
    private ?\DateTimeInterface $updatedAt = null;
    
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class, orphanRemoval: true)]
    #[Groups(['read'])]
    public $comments = [];
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read'])]
    public User $author;
    
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'post_tag')]
    #[Groups(['read', 'post:create', 'tag:read'])]
    private Collection $tags;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'post:image'])]
    private ?string $filePath; 
    
    #[Vich\UploadableField(
    mapping: 'media_object',
    fileNameProperty: 'filePath',
    mimeType: 'mimeType', 
    )]
    #[Groups(['post:create', 'post:update'])]
    private ?File $file = null;
    
    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['read'])]
    private ?string $contentUrl;
    
    public function __construct()
    {
        $this->publishedAt = new \DateTime("now");
        $this->updatedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getTitle() : ?string
    {
        return $this->title;
    }
    public function setTitle(string $title) : self
    {
        $this->title = $title;
        return $this;
    }
    public function getSummary() : ?string
    {
        return $this->summary;
    }
    public function setSummary(string $summary) : self
    {
        $this->summary = $summary;
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
    public function getPublishedAt() : ?\DateTimeInterface
    {
        return $this->publishedAt;
    }
    public function setPublishedAt(\DateTimeInterface $publishedAt) : self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }
    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeInterface $updatedAt) : self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    /**
     * @return File|NULL
     */
    public function getFile()
    {
        return $this->file;
    }
    /**
     * @param File|null $file
     * @return Publication
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }
    /**
     * @return Collection|Comment[]
     */
    public function getComments() : Collection
    {
        return $this->comments;
    }
    public function addComment(Comment $comment) : self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setPost($this);
        }
        return $this;
    }
    public function removeComment(Comment $comment) : self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
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
     * @return Collection|Tag[]
     */
    public function getTags() : Collection
    {
        return $this->tags;
    }
    public function addTag(Tag $tag) : self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }
        return $this;
    }
    public function removeTag(Tag $tag) : self
    {
        $this->tags->removeElement($tag);
        return $this;
    }
    public function getFilePath() : ?string
    {
        return $this->filePath;
    }
    public function setFilePath(?string $filePath) : self
    {
        $this->filePath = $filePath;
        return $this;
    }
    
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }
    
    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): self
    {
        $this->contentUrl = $contentUrl;
    }

    

}
