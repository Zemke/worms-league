<?php

namespace App\Entity;

use App\Repository\ReplayRepository;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Entity\Game;
use Symfony\Component\HttpFoundation\File\File;

// TODO unique checksum validation

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ReplayRepository::class)]
#[Vich\Uploadable]
class Replay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Vich\UploadableField(
        mapping: 'replay',
        fileNameProperty: 'name',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalName',)]
    private $file;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'integer')]
    private $size;

    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mimeType;

    #[ORM\Column(type: 'string', length: 255)]
    private $originalName;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'replays')]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\Column(type: 'datetime')]
    private $modified;

    #[ORM\OneToOne(
        targetEntity: ReplayData::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove'])]
    private $replayData;

    #[ORM\OneToOne(
        targetEntity: ReplayMap::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove'])]
    private $replayMap;

    #[ORM\Column(type: 'string', length: 16, nullable: true, unique: true)]
    #[Assert\UniqueEntity(message: 'Replay does already exist in another game')]
    private $cksum;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->modified = $this->created;
    }

    public function winner(): ?User
    {
        if (!$this->processed()) {
            throw new \RuntimeException("Replay {$this->id} is not processed");
        }
        $winner = $this->replayData->winner();
        if (is_null($winner)) {
            return null;
        }
        $match = $this->replayData->matchUsers($this->game->getHome(), $this->game->getAway());
        return $this->game->getHome()->getId() === $match[$winner]->getId()
            ? $this->game->getHome() : $this->game->getAway();
    }

    public function processed(): bool
    {
        return !is_null($this->getReplayData()) && !empty($this->getReplayData()->getData());
    }

    public function homeColor(): ?string
    {
        return $this->color($this->game->getHome());
    }

    public function awayColor(): ?string
    {
        return $this->color($this->game->getAway());
    }

    public function winnerColor(): ?string
    {
        $winner = $this->winner();
        if (is_null($winner)) {
            return null;
        } else if ($winner->getId() === $this->game->getHome()->getId()) {
            return $this->homeColor();
        } else {
            return $this->awayColor();
        }
    }

    private function color(User $user): ?string
    {
        if (!$this->processed()) {
            return null;
        }
        $m = $this->replayData->matchUsers($this->game->getHome(), $this->game->getAway());
        foreach ($m as $n => $u) {
            if ($u->getId() === $user->getId()) {
                if ($this->replayData->getData()['teams'][0]['user'] === $n) {
                    return $this->replayData->getData()['teams'][0]['color'];
                } else {
                    return $this->replayData->getData()['teams'][1]['color'];
                }
            }
        }
        return null;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModified()
    {
        $this->modified = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param File|UploadedFile|null $file
     */
    public function setFile(?File $file = null)
    {
        $this->file = $file;
        if (null !== $file) {
            $this->modified = new \DateTime();
        }
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

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

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getReplayData(): ?ReplayData
    {
        return $this->replayData;
    }

    public function setReplayData(ReplayData $replayData): self
    {
        $this->replayData = $replayData;

        return $this;
    }

    public function getReplayMap(): ?ReplayMap
    {
        return $this->replayMap;
    }

    public function setReplayMap(?ReplayMap $replayMap): self
    {
        $this->replayMap = $replayMap;

        return $this;
    }

    public function getCksum(): ?string
    {
        return $this->cksum;
    }

    public function setCksum(?string $cksum): self
    {
        $this->cksum = $cksum;

        return $this;
    }
}

