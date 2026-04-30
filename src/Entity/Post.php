<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $media_1_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $media_2_url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $media_3_url = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserProfile $user = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'posts')]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMedia1Url(): ?string
    {
        return $this->media_1_url;
    }

    public function setMedia1Url(string $media_1_url): static
    {
        $this->media_1_url = $media_1_url;

        return $this;
    }

    public function getMedia2Url(): ?string
    {
        return $this->media_2_url;
    }

    public function setMedia2Url(?string $media_2_url): static
    {
        $this->media_2_url = $media_2_url;

        return $this;
    }

    public function getMedia3Url(): ?string
    {
        return $this->media_3_url;
    }

    public function setMedia3Url(?string $media_3_url): static
    {
        $this->media_3_url = $media_3_url;

        return $this;
    }

    public function getUser(): ?UserProfile
    {
        return $this->user;
    }

    public function setUser(?UserProfile $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }
}
