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
use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Controller\PostController;
use App\Controller\PublicationUpdateController;
use App\Controller\UpdatePublicationFileAction;
use App\State\PublicationProcessor;

#[Vich\Uploadable]
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
        processor: PublicationProcessor::class,
        security: "is_granted('ROLE_USER')",
        inputFormats: ['multipart' => ['multipart/form-data']],
        validationContext: ['groups' => ['Default', 'publication:create']],
        denormalizationContext: ['groups' => ['publication:create', 'tag:item:get']]
        ),
    new Post(
        security: "is_granted('ROLE_USER')",
        uriTemplate: '/publications/{id}/fichier',
        controller: UpdatePublicationFileAction::class,
        read: false,
        inputFormats: ['multipart' => ['multipart/form-data']],
       // headers: ['accept' => ['multipart/form-data']],
        deserialize: false,   // très important,
        serialize: false,
        openapi: new Model\Operation(
            summary: 'Met à jour le fichier de la publication',
            description: '# Mise à jour du fichier media de la publication',
            requestBody: new Model\RequestBody(
                content: new \ArrayObject([
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'file' => ['type' => 'file']
                            ]
                        ],
                        'example' => [
                            'file' => 'fichier'
                        ]
                    ]
                ])),
            responses: [
                204 => new Model\Response(
                    description: 'Fichier mis à jour avec succès.')
            ]
            )
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
        inputFormats: ['json' => 'application/merge-patch+json'],
        //controller: PublicationUpdateController::class,
        
        ),
    new Delete(security: "is_granted('ROLE_ADMIN') or object.author == user"),
]
)]
#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[UniqueEntity("title")]
class Publication
{
    #[ApiProperty(identifier: true)]
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
    
    #[ORM\Column(type: 'text')]
    #[Groups(['publication:read', 'publication:create', 'publication:update'])]
    private $content = null;
    
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
    #[Groups(['publication:read', 'publication:create', 'publication:update', 'tag:item:get'])]
    #[Link(toProperty: 'publication')]
    private Collection $tags;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['publication:read'])]
    private ?string $filePath; 
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['publication:read'])]
    private ?string $coverPath = null;
    
//     #[Assert\File(
//         maxSize: '10M',
//         maxSizeMessage: 'Le fichier est trop volumineux: { size }. La limite est { limit }',
//         mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
//             'audio/mpeg', 'audio/ogg', 'audio/wav',
//             'video/mp4', 'video/webm', 'video/ogg'
//         ],
//         mimeTypesMessage: 'Ce type de fichier n\'est pas autorisé: les types autorisés sont { types }'
//         )]
    #[Assert\NotNull(groups: ['publication:image'])]
    #[Vich\UploadableField(
    mapping: 'uploads',
    fileNameProperty: 'filePath',
    mimeType: 'mimeType',    
    )]
    #[Groups(['publication:create', 'publication:image'])]
    private ?File $file = null;    
  
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mimeType = null;
    
    public function __construct()
    {
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
     * @@param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $file
     * @return Publication
     */
    public function setFile(?File $file = null): self
    {
        $this->file = $file;
        
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
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
    
    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }
    
    public function setCoverPath(?string $coverPath): self
    {
        $this->coverPath = $coverPath;
        return $this;
    }
    
    public function __toString() {
        return $this->title;
    }
    
    #[Mercure\Expose]
    public function getMercureTopics(): array
    {
        return [
            'https://knarfmedia.local.dev/publications/' . $this->getId(),
            'https://knarfmedia.local.dev/publications/' . $this->getId() . '/comments'
        ];
    }



    

}
