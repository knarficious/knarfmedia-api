<?php 

// src/Entity/MediaObject.php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use App\Controller\CreateMediaObjectAction;
use App\Repository\MediaObjectRepository;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[ApiResource(
    types: ['https://schema.org/MediaObject'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            controller: CreateMediaObjectAction::class,
            uriTemplate: '/medias',
         //   uriVariables: ['publicationId' => new Link(fromClass: Publication::class, toProperty: 'publication', identifiers: ['id', 'slug'],)],
            deserialize: false,
            validationContext: ['groups' => ['media_object_create']],
            inputFormats: ['multipart' => ['multipart/form-data']],
            openapi: new Model\Operation(
                summary: 'Téléverse les fichiers',
                description: 'Téléversement des fichiers liés à une publication',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject(
                        [
                            'multipart/form-data' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'file' => [
                                            'type' => 'string',
                                            'format' => 'binary'
                                        ]
                                    ]
                                ]
                            ]
                        ])))
            ),
    ],
    normalizationContext: ['groups' => ['media_object:read']],
    denormalizationContext: ['groups' => ['media_object:write']],
    )]
    class MediaObject
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;
        
        #[Vich\UploadableField(mapping: 'uploads', fileNameProperty: 'fileName', originalName: 'fileOriginalName')]
        #[Assert\NotNull(groups: ['media_object_create'])]
        #[Groups(['media_object:write'])]
        private ?File $file = null;
        
        #[ORM\Column(nullable: true)]
        #[Groups(['media_object:read', 'publication:read'])]
        private ?string $fileName = null;
        
        #[ORM\Column(nullable: true)]
        #[Groups(['media_object:read', 'publication:read'])]
        private ?string $fileOriginalName = null;
        
        #[ApiProperty(types: ['https://schema.org/contentUrl'])]
        #[ORM\Column(nullable: true)]
        #[Groups(['media_object:read', 'publication:read'])]
        public ?string $contentUrl = null; // généré par un listener ou un Serializer
        
        #[ORM\Column(nullable: true)]
        private ?\DateTimeImmutable $updatedAt = null;
        
        #[ORM\ManyToOne(inversedBy: 'files')]
        #[ORM\JoinColumn(nullable: false)]
        #[Groups(['media_object:read', 'media_object:write'])]
        private ?Publication $publication = null;
        
        public function getId(): ?int
        {
            return $this->id;
        }
      
        public function setFile(?File $file): self
        {
            $this->file = $file;
            
            if( null !== $file) {
                $this->updatedAt = new \DateTimeImmutable();
            }
            return $this;
        }
        
        public function getFile(): ?File
        {
            return $this->file;
        }
        
        public function getFileName(): ?string
        {
            return $this->fileName;
        }
        
        public function setFileName(?string $fileName): self
        {
            $this->fileName = $fileName;
            
            return $this;
        }
        
        public function getPublication(): ?Publication
        {
            return $this->publication;
        }
        
        public function setPublication(?Publication $publication): self
        {
            $this->publication = $publication;
            return $this;
        }
        /**
         * @return string
         */
        public function getFileOriginalName(): ?string
        {
            return $this->fileOriginalName;
        }
    
        /**
         * @return string
         */
        public function getContentUrl(): ?string
        {
            return $this->contentUrl;
        }
    
        /**
         * @return \App\Entity\DateTimeImmutable
         */
        public function getUpdatedAt(): ?\DateTimeImmutable
        {
            return $this->updatedAt;
        }
    
        /**
         * @param string $fileOriginalName
         */
        public function setFileOriginalName($fileOriginalName): void
        {
            $this->fileOriginalName = $fileOriginalName;
        }
    
        /**
         * @param string $contentUrl
         */
        public function setContentUrl($contentUrl): void
        {
            $this->contentUrl = $contentUrl;
        }
    
        /**
         * @param \App\Entity\DateTimeImmutable $updatedAt
         */
        public function setUpdatedAt($updatedAt): void
        {
            $this->updatedAt = $updatedAt;
        }
        
    
        
        
}