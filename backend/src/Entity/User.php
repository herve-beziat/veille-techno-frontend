<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const AVAILABLE_ROLES = [
        'ROLE_ADMIN',
        'ROLE_USER',
        'ROLE_TESTER',
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * @var Collection<int, BoardList>
     */
    #[ORM\OneToMany(targetEntity: BoardList::class, mappedBy: 'owner')]
    private Collection $boardLists;

    public function __construct()
    {
        $this->boardLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    
    public function getRoles(): array
    {
        return $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, BoardList>
     */
    public function getBoardLists(): Collection
    {
        return $this->boardLists;
    }

    public function addBoardList(BoardList $boardList): static
    {
        if (!$this->boardLists->contains($boardList)) {
            $this->boardLists->add($boardList);
            $boardList->setOwner($this);
        }

        return $this;
    }

    public function removeBoardList(BoardList $boardList): static
    {
        if ($this->boardLists->removeElement($boardList)) {
            // set the owning side to null (unless already changed)
            if ($boardList->getOwner() === $this) {
                $boardList->setOwner(null);
            }
        }

        return $this;
    }
    public function eraseCredentials(): void
    {
    }
}
