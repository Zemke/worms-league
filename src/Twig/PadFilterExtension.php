<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PadFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('pad', [$this, 'padFn'], ['is_safe' => ['html']]),
        ];
    }

    public function padFn(string $v, \Traversable|array $path, string $node): string
    {
        $vv = array_map(
            fn($v) => is_array($v) ? $v[$node] : $v->$node(),
            is_array($path) ? $path : iterator_to_array($path));
        $mx_v = array_reduce($vv, fn($acc, $v) => max($acc, strlen($v)), 0);
        return str_repeat('&nbsp;', $mx_v - strlen($v)) . $v;
    }
}
