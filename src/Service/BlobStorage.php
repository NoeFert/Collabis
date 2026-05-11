<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BlobStorage
{
    private const API_URL = 'https://vercel.com/api/blob/';
    private const API_VERSION = '12';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function uploadPublic(UploadedFile $file, string $pathname): string
    {
        $pathname = ltrim($pathname, '/');
        $token = $this->getToken();

        if ($token === null) {
            return $this->storeLocally($file, $pathname);
        }

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $handle = fopen($file->getPathname(), 'rb');

        if ($handle === false) {
            throw new FileException('Impossible de lire le fichier a envoyer.');
        }

        try {
            $response = $this->httpClient->request('PUT', self::API_URL.'?'.http_build_query([
                'pathname' => $pathname,
            ]), [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => $mimeType,
                    'x-api-version' => self::API_VERSION,
                    'x-vercel-blob-access' => 'public',
                    'x-content-type' => $mimeType,
                    'x-content-length' => (string) ($file->getSize() ?? 0),
                    'x-add-random-suffix' => '0',
                    'x-allow-overwrite' => '0',
                ],
                'body' => $handle,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode >= 400) {
                throw new RuntimeException(sprintf('Upload Vercel Blob refuse (%d): %s', $statusCode, $content));
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['url']) || !is_string($data['url'])) {
                throw new RuntimeException('Vercel Blob n\'a pas retourne d\'URL exploitable.');
            }

            return $data['url'];
        } finally {
            fclose($handle);
        }
    }

    private function storeLocally(UploadedFile $file, string $pathname): string
    {
        $directory = dirname($pathname);
        $filename = basename($pathname);
        $targetDirectory = $this->projectDir.'/public/uploads';

        if ($directory !== '.') {
            $targetDirectory .= '/'.$directory;
        }

        $file->move($targetDirectory, $filename);

        return $pathname;
    }

    private function getToken(): ?string
    {
        $token = getenv('BLOB_READ_WRITE_TOKEN') ?: ($_ENV['BLOB_READ_WRITE_TOKEN'] ?? $_SERVER['BLOB_READ_WRITE_TOKEN'] ?? null);

        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        return trim($token);
    }
}
