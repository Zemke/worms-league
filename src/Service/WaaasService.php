<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\HttpFoundation\File\File;

class WaaasService
{

    public function __construct(private HttpClientInterface $client,
                                private string $waaas)
    {}

    public function send(File $file): array
    {
        //$url = $this->params->get('waaas');
        $url = $this->waaas;
        dump($url);
        $form = ['replay' => DataPart::fromPath($file->getPathname())];
        $dataPart = new FormDataPart($form);
        $res = $this->client->request('POST', $url, [
            'headers' => $dataPart->getPreparedHeaders()->toArray(),
            'body' => $dataPart->bodyToIterable(),
        ]);
        dump($res->toArray());
        return $res->toArray();
    }
}


