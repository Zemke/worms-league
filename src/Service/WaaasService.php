<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use App\Entity\ReplayData;
use App\Entity\Replay;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Vich\UploaderBundle\Storage\StorageInterface;

class WaaasService implements \Psr\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private HttpClientInterface $client,
                                private StorageInterface $storage,
                                private string $waaas)
    {}

    /**
     * Send the replay file to WAaaS for further processing and construct
     * ReplayData from the response.
     *
     * @return ReplayData Instantiated with the data from the response.
     * @throws \RuntimeException When the HTTP request itself has errored.
     */
    public function send(Replay $replay): ReplayData
    {
        $dataPart = new FormDataPart([
            'replay' => DataPart::fromPath($this->storage->resolvePath($replay, 'file'))
        ]);
        $res = $this->client->request('POST', $this->waaas, [
            'headers' => $dataPart->getPreparedHeaders()->toArray(),
            'body' => $dataPart->bodyToIterable(),
        ]);
        try {
            return (new ReplayData())->setData($res->toArray());
        } catch (HttpClientException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function map(string $mapUrl): void
    {
        $res = $this->client->request($mapUrl);
    }
}

