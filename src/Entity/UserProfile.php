<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class UserProfile implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar_url = null;

    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user')]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'user_profile')]
    private Collection $conversations;

    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'interlocutor')]
    private Collection $interlocutorConversations;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $messages;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'interlocutor')]
    private Collection $conversationsAsInterlocutor;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->interlocutorConversations = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->conversationsAsInterlocutor = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // Garantit au moins un rôle
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function setAvatarUrl(?string $avatar_url): static
    {
        $this->avatar_url = $avatar_url;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Nettoyer les données sensibles temporaires si besoin
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    // --- RELATIONS ---

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function getInterlocutorConversations(): Collection
    {
        return $this->interlocutorConversations;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversationsAsInterlocutor(): Collection
    {
        return $this->conversationsAsInterlocutor;
    }

    public function addConversationsAsInterlocutor(Conversation $conversationsAsInterlocutor): static
    {
        if (!$this->conversationsAsInterlocutor->contains($conversationsAsInterlocutor)) {
            $this->conversationsAsInterlocutor->add($conversationsAsInterlocutor);
            $conversationsAsInterlocutor->setInterlocutor($this);
        }

        return $this;
    }

    public function removeConversationsAsInterlocutor(Conversation $conversationsAsInterlocutor): static
    {
        if ($this->conversationsAsInterlocutor->removeElement($conversationsAsInterlocutor)) {
            // set the owning side to null (unless already changed)
            if ($conversationsAsInterlocutor->getInterlocutor() === $this) {
                $conversationsAsInterlocutor->setInterlocutor(null);
            }
        }

        return $this;
    }
}