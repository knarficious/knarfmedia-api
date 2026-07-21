<?php

namespace App\Entity;

use App\Repository\RubriqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    formats: ['jsonld' => ['application/ld+json']],
    normalizationContext: ['groups' => ['rubrique:read', 'rubrique:item:get']],
    denormalizationContext: ['groups' => ['rubrique:write'] ],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['rubrique:read', 'rubrique:item:get']],),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')"
            ),
        new Put(
            security: "is_granted('ROLE_USER')"
            ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.author == user"
            ),
    ]
    )]
#[ORM\Entity(repositoryClass: RubriqueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Il y a déjà une rubrique de ce nom')]
class Rubrique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rubrique:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 75)]
    #[Groups(['rubrique:read', 'rubrique:write', 'publication:read'])]
    private ?string $titre = null;

    /**
     * @var Collection<int, Publication>
     */
    #[ORM\OneToMany(mappedBy: 'rubrique', targetEntity: Publication::class)]
    #[Groups(['rubrique:item:get'])]
    private Collection $publications;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Groups(['rubrique:read', 'rubrique:write', 'publication:read'])]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    #[Groups(['rubrique:read', 'rubrique:write', 'publication:read'])]
    private Collection $children;

    #[ORM\Column(length: 95, nullable: true)]
    #[Groups(['rubrique:read', 'rubrique:write'])]
    private ?string $slug = null;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
        $this->children = new ArrayCollection();
    }
    
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug($this->titre)->lower();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        return $this->publications;
    }

    public function addPublication(Publication $publication): static
    {
        if (!$this->publications->contains($publication)) {
            $this->publications->add($publication);
            $publication->setRubrique($this);
        }

        return $this;
    }

    public function removePublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication)) {
            // set the owning side to null (unless already changed)
            if ($publication->getRubrique() === $this) {
                $publication->setRubrique(null);
            }
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
    
    public function __toString() {
        return $this->slug;
    } 
}
