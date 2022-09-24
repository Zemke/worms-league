<?php

namespace App\Entity;

use App\Repository\ReplayDataRepository;
use App\Entity\Texture;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ReplayDataRepository::class)]
class ReplayData
{
    const COLORS = [
        'green' => '#80FF80',
        'blue' => '#9D9FFF',
        'red' => '#FF7F7F',
        'yellow' => '#FFFF80',
        'cyan' => '#80FFFF',
        'magenta' => '#FF82FF',
    ];

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'json')]
    private $data = [];

    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\Column(type: 'datetime')]
    private $modified;

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->modified = $this->created;
    }

    /**
     * Terrain texture object based on the data of the replay.
     *
     * @return Texture can be null when the data is not available
     * @throws UnhandledMatchError when the data is not availabe but cannot be matched
     */
    public function texture(): ?Texture
    {
        if (is_null($this->data) || is_null($this->data['texture'])) {
            return null;
        }
        return Texture::parse($this->data['texture']);
    }

    /**
     * Convenience method to get the startedAt as a DateTime object.
     */
    public function startedAt(): \DateTime
    {
        return new \DateTime($this->data['startedAt']);
    }

    /**
     * The in-game users' names.
     *
     * @return string[]
     */
    public function names(): array
    {
        return array_map(fn($v) => $v['user'], $this->data['teams']);
    }

    /**
     * Find the winner by accumulating kills. The winner is the one with fewer victims.
     *
     * @return string Winning in-game user's name or null if drawn.
     */
    public function winner(): ?string
    {
        $names = $this->names();
        [$hour, $minute, $second] = explode(':', $this->data['gameEnd']); // 00:25:13.98
        $past = (intval($hour) * 60 * 60) + (intval($minute) * 60) + intval($second);
        foreach ($this->data['turns'] as $turn) {
            if (in_array('Surrender', $turn['weapons'])) {
                if ($past > 90) {
                    return $names[0] === $turn['user'] ? $names[1] : $names[0];
                } else {
                    return null; // propbably just skipping the round
                }
            }
        }

        // A hint that might be interesting in the future:
        // A disconnect/quit round might be identifiable by the last turn
        // having to retreat and false lossOfControl

        $victims = array_reduce($this->data['turns'], function($acc, $turn) use ($names) {
            foreach ($turn['damages'] as $dmg) {
                if (!in_array($dmg['victim'], $names)) {
                    throw new \RuntimeException(sprintf(
                        '%s is not in names %s',
                        $dmg['victim'], json_encode($names)));
                }
                $acc[$dmg['victim']] += $dmg['kills'];
            }
            return $acc;
        }, [$names[0] => 0, $names[1] => 0]);
        $c = array_values($victims)[0] - array_values($victims)[1];
        $winner = null;
        if ($c === 0) {
            if (array_key_exists('winsTheRound', $this->data) && !empty($this->data['winsTheRound'])) {
                return (array_combine(
                    array_column($this->data['teams'], 'team'),
                    array_column($this->data['teams'], 'user')))[$this->data['winsTheRound']];
            }
            return null;
        } else {
            return $c > 0 ? array_keys($victims)[1] : array_keys($victims)[0];
        }
    }

    /**
     * Match user to in-game users' names.
     *
     * @return array Association of in game user's name to User object.
     */
    public function matchUsers($ua, $ub): array
    {
        [$n1, $n2] = $this->names();
        $mxmatch = array_reduce([$ua, $ub], function ($mx, $u) use ($n1, $n2) {
            $sm = [$u->similarUsername($n1), $u->similarUsername($n2)];
            $mxp = max($sm);
            if (is_null($mx) || $mxp > $mx[1]) {
                $mxu = $sm[0] > $sm[1] ? [$u, $n1] : [$u, $n2];
                return [$mxu, $mxp];
            }
            return $mx;
        }, null);
        $res = [$mxmatch[0][1] => $mxmatch[0][0]];
        $res[$mxmatch[0][1] === $n1 ? $n2 : $n1] = $mxmatch[0][0]->getId() === $ua->getId() ? $ub : $ua;
        return $res;
    }

    #[Assert\IsTrue(message: 'Players are not two.')]
    public function isDataTwoNames(): bool
    {
        return empty($this->getData()) || count($this->names()) === 2;
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

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

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
