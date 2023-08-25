<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
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
use App\Controller\PostImageController;
use App\Controller\PostCommentController;

#[Vich\Uploadable]
#[ApiResource(
operations: [
    new Get(), 
    new Post(
        security: "is_granted('ROLE_ADMIN') or object.author == user", 
        uriTemplate: '/publications/{id}/image', 
        controller: PostImageController::class, 
        deserialize: false, 
        openapiContext: 
            ['summary' => 'Creates an image resource',
                'requestBody' => 
                ['description' => 'Uploads a media file',
                    'content' => 
                    ['multipart/form-data' => 
                        ['schema' => 
                            ['type' => 'object', 
                                'properties' => 
                                ['file' => 
                                    ['type' => 'string', 
                                        'format' => 'binary'
                                    ]                                    
                                ]                                
                            ]                            
                        ]                        
                    ]                    
                ]                
            ]
        ),
    new Put(security: "is_granted('ROLE_ADMIN') or object.author == user"),
    new Delete(security: "is_granted('ROLE_ADMIN') or object.author == user"),
    new GetCollection(),
    new Post(
        security: "is_granted('ROLE_USER')",
        controller: PostController::class)
],
normalizationContext: ['groups' => ['read', 'read:Publication']],
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
    #[Groups(['read', 'post:create', 'post:update'])]
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
    
    #[ApiProperty(fetchEager: true)]
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class, orphanRemoval: true)]
    #[Groups(['read'])]
    private $comments;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[Groups(['read', 'post:update'])]
    public User $author;
    
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'post_tag')]
    #[Groups(['read', 'read:Publication', 'post:update', 'post:create'])]
    private $tags;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['read', 'post:image'])]
    private $filePath; 
    
    #[Vich\UploadableField(
    mapping: 'media_object',
    fileNameProperty: 'filePath',
    size: 'size', 
    mimeType: 'mimeType', 
    originalName: 'originalName',
    dimensions: 'dimensions'
    )]
    private ?File $file = null;
    
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

}
