<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Recorder;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FileRecorder implements RecorderInterface
{
    private string $folder;
    private $keyGenerator;
    private ResponseSerializerInterface $responseSerializer;

    public function __construct(string $folder, callable $keyGenerator, ResponseSerializerInterface $responseSerializer)
    {
        $this->folder = $folder;
        $this->keyGenerator = $keyGenerator;
        $this->responseSerializer = $responseSerializer;
    }

    public function record(string $method, string $url, array $options, ResponseInterface $response): void
    {
        $filePath = $this->getFilepath($method, $url, $options);

        if (false === $fp = @fopen($filePath, 'w')) {
            throw new RecordException(sprintf('Unable to save response in "%s".', $filePath));
        }

        @fwrite($fp, $this->responseSerializer->serialize($response));
        @fclose($fp);

        @chmod($this->folder, 0666 & ~umask());
    }

    public function replay(string $method, string $url, array $options): ?MockResponse
    {
        $filePath = $this->getFilepath($method, $url, $options);

        $serializedResponse = is_file($filePath) && false !== ($contents = @file_get_contents($filePath)) ? $contents : null;

        if (null === $serializedResponse) {
            return null;
        }

        return MockResponse::fromRequest($method, $url, $options, $this->responseSerializer->deserialize($serializedResponse));
    }

    private function getFilepath(string $method, string $url, array $options): string
    {
        $key = ($this->keyGenerator)($method, $url, $options);

        if (!is_dir(\dirname($this->folder)) && false === @mkdir(\dirname($this->folder), 0777, true) && !is_dir(\dirname($this->folder))) {
            throw new RecordException(sprintf('Unable to find "%s" folder.', $this->folder));
        }

        return $this->folder.\DIRECTORY_SEPARATOR.$key;
    }
}
