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
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Controller\PublicationUpdateController;
use App\Controller\UpdatePublicationFileAction;
use App\State\PostPublicationProcessor;
use App\State\PublicationUpdateProcessor;

#[ApiResource(
    mercure: true,
    formats: ['jsonld' => ['application/ld+json'], 'multipart' => ['multipart/form-data']],
    normalizationContext: ['groups' => ['publication:read', 'tag:read']],
    denormalizationContext: ['groups' => ['publication:create', 'publication:update', 'publication:image']],
    operations: [   
    new Get(),
    new GetCollection(),
    new GetCollection(
        uriTemplate: '/users/{userId}/publications',
        uriVariables: [
            'userId' => new Link(fromClass: User::class, toProperty: 'author')
        ]), 
    new Post(
        processor: PostPublicationProcessor::class,
        security: "is_granted('ROLE_USER')",
        inputFormats: ['multipart' => ['multipart/form-data']],
        validationContext: ['groups' => ['Default', 'publication:create']],
        denormalizationContext: ['groups' => ['publication:create', 'tag:item:get']]
        ),
    new Put(
        security: "is_granted('ROLE_ADMIN') or object.author == user",
        inputFormats: ['json' => ['application/ld+json']],
        controller: PublicationUpdateController::class,
        options: ['methods' => 'POST'],
        denormalizationContext: ['groups' => ['publication:update', 'tag:write']]
        ),
    new Patch(
        security: "is_granted('ROLE_ADMIN') or object.author == user",
        inputFormats: ['json' => ['application/merge-patch+json']],
        outputFormats: ['jsonld' => ['application/ld+json']],
        processor: PublicationUpdateProcessor::class,
        normalizationContext: ['groups' => ['publication:read']],
        denormalizationContext: ['groups' => ['publication:write']],
        ),
    new Delete(security: "is_granted('ROLE_ADMIN') or object.author == user"),
]
)]
#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[UniqueEntity("slug")]
class Publication
{    
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['publication:read', 'comment:write'])]
    private $id;
    
    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['publication:read', 'publication:create', 'publication:update', 'tag:read'])]
    private $title = null;
    
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['publication:read', 'publication:create', 'publication:update', 'tag:read'])]
    private $summary = null;
    
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['publication:read', 'publication:create', 'publication:update'])]
    private array $content = [];
    
    #[ApiProperty(iris: ['https://schema.org/dateCreated'])]
    #[ORM\Column(type: 'date')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['publication:read', 'tag:read'])]
    private ?\DateTimeInterface $publishedAt = null;
    
    #[ApiProperty(iris: ['https://schema.org/dateModified'])]
    #[ORM\Column(type: 'date')]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Groups(['publication:read', 'tag:read'])]
    private ?\DateTimeInterface $updatedAt = null;
    
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: Comment::class, orphanRemoval: true)]
    #[Groups(['publication:read', 'comment:read', 'comment:item:get'])]
    private Collection $comments;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['publication:read', 'tag:read'])]
    #[Link(toProperty: 'author')]
    private ?User $author;
    
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'publications')]
    #[ORM\JoinTable(name: 'post_tag')]
    #[Groups(['publication:read', 'publication:create', 'publication:update'])]
    #[Link(toProperty: 'publication')]
    private Collection $tags;
    
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: MediaObject::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['publication:read'])]
    private Collection $files;

    #[ApiProperty(identifier: true)]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['publication:read', 'publication:update', 'tag:read'])]
    private ?string $slug = null;
    
    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->files = new ArrayCollection();
    }
    
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug($this->title)->lower();
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
    
    public function getContent() : ?array
    {
        return $this->content;
    }
    
    public function setContent(array $content) : self
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
     * @return Collection|Comment[]
     */
    public function getComments()
    {
        return $this->comments->getValues();
    }
    
    public function addComment(Comment $comment) : self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPublication($this);
        }
        
        return $this;

    }
    
    public function removeComment(Comment $comment) : self
    {
        $this->comments->removeElement($comment);
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
            $this->tags->add($tag);
            $tag->addPublication($this);
        }
        return $this;
    }
    
    public function removeTag(Tag $tag) : self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->getPublications()->removeElement($this);
        }  
        
        return $this;
    }
    
    /**
     * @return Collection|MediaObject[]
     */
    
    public function getFiles(): Collection
    {
        return $this->files;
    }
    
    public function addFile(MediaObject $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setPublication($this);
        }
        return $this;
    }
    
    public function removeFile(MediaObject $file): self
    {
        if ($this->files->removeElement($file)) {
            if ($file->getPublication() === $this) {
                $file->setPublication(null);
            }
        }
        return $this;
    }   

    
    public function __toString() {
        return $this->slug;
    }
    
    #[Mercure\Expose]
    public function getMercureTopics(): array
    {
        return [
            'https://knarfmedia.local.dev/publications/' . $this->getId(),
            'https://knarfmedia.local.dev/publications/' . $this->getId() . '/comments'
        ];
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }



    

}
