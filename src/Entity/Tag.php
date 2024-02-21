<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Doctrine\Common\Collections\ArrayCollection;

#[ApiResource(
operations: [
    new Get(
        normalizationContext: [
            'groups' => ['tag:read', 'tag:item:get']
        ]),
    new GetCollection(),
    new Post(security: "is_granted('ROLE_USER')"),
    new Put(security: "is_granted('ROLE_USER')"),
    new Patch(security: "is_granted('ROLE_USER')"),
    new Delete(security: "is_granted('ROLE_ADMIN')")
],
normalizationContext: [
    'groups' => ['tag:read', 'read:Publication'],
],
denormalizationContext: [
    'groups' => ['tag:write'],
],
)]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Il y a déjà un tag de ce nom')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['tag:read', 'post:create'])]
    private $id;
    
    #[ORM\Column(type: 'string', length: 25)]
    #[Groups(['tag:read', 'tag:write', 'read:Publication', 'post:create'])]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]    
    #[Assert\NotBlank]
    private $name;
    
    #[ORM\ManyToMany(targetEntity: Publication::class, inversedBy: 'tags')]
    #[Groups(['tag:item:get'])]
    #[Assert\Valid]
    protected Collection $publications;
    
    public function __construct(){
        $this->publications = new ArrayCollection();
    }
    
    public function getId() : ?int
    {
        return $this->id;
    }
    public function getName() : ?string
    {
        return $this->name;
    }
    public function setName(?string $name) : self
    {
        $this->name = $name;
        return $this;
    }    
    
    public function getPublications(): Collection {
        return $this->publications;
    }
    
    public function addPublication(Publication $publication) {
        
        if (!$this->publications->contains($publication)) {
            $this->publications[] = $publication;
            $publication->addTag($this);
        }
        
    }
    
    public function __toString() {
        return $this->name;
    }
}
