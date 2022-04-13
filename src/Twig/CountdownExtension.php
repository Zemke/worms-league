<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CountdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('countdown', [$this, 'countdownFn']),
        ];
    }

    public function countdownFn(\DateTime $v, $op = 'days', $preWord = 'in'): string
    {
        $t = new \DateTime('today');
        $interval = $t->diff($v);
        if ($interval->$op === 0) {
            return $op === 'days' ? 'today' : 'now';
        }
        if (!str_ends_with($preWord, ' ')) {
            $preWord = $preWord . ' ';
        }
        return $interval->format($preWord . '%a ' . substr($op, 0, -1) . ($interval->days > 1 ? 's' : ''));
    }
}
