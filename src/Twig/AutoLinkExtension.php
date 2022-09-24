<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AutoLinkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('auto_link', [$this, 'autoLinkFn']),
        ];
    }

    public function autoLinkFn($v): string
    {
        // links
        $v =  preg_replace(
            '/(?<!@)(\\b(?:http[s]?:\\/\\/)[a-z0-9-.]+?\\.(?:[a-z]{2,})(?:[.a-z0-9-\\/]+)?(?:[^\\s]*)?\\b)/i',
            '<a href="http://$1" target="_blank">$1</a>',
            $v);
        $v = preg_replace(
            '/http?:\/\/(http[s]?:\/\/)/i',
            '$1',
            $v);

        // mail
        $v = preg_replace(
            '/(\b\S+@\S+\b)/i',
            '<a href="mailto:$1">$1</a>',
            $v);

        return $v;
    }
}

