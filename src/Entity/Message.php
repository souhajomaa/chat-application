<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(['id'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: "messages")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "messages")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user'])]
    private ?User $user = null;

    #[ORM\Column(type: "text")]
    #[Groups(['content'])]
    private ?string $content = null;

    #[ORM\Column(type: "datetime_immutable")]
    #[Groups(['createdAt'])]
    private ?DateTimeImmutable $createdAt = null;

    #[Groups(['mine'])]
    private bool $mine = false;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable(); // Date de création automatique
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isMine(): bool
    {
        return $this->mine;
    }

    public function setMine(bool $mine): static
    {
        $this->mine = $mine;
        return $this;
    }
}
