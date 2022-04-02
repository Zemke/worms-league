<?php

namespace App\Entity;

use App\Repository\ReplayMapRepository;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ReplayMapRepository::class)]
#[Vich\Uploadable]
class ReplayMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Vich\UploadableField(
        mapping: 'map',
        fileNameProperty: 'name',
        size: 'size',
        mimeType: 'mimeType',
        originalName: 'originalName',)]
    private $file;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'integer')]
    private $size;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mimeType;

    #[ORM\Column(type: 'string', length: 255)]
    private $originalName;

    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\Column(type: 'datetime')]
    private $modified;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->modified = $this->created;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

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
}
