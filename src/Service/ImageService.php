<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Service;

use DateTime;
use Exception;
use Liip\ImagineBundle\Message\WarmupCache;
use Liip\ImagineBundle\Service\FilterService;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

class ImageService
{
    public function __construct(#[Autowire(service: 'liip_imagine.service.filter')] private readonly FilterService $filterService, private readonly MessageBusInterface $messageBus, private readonly StorageInterface $storage)
    {
    }

    public function warmupCache(object $object, bool $overwrite = true, string $field = 'image'): void
    {
        $path = $this->storage->resolveUri($object, sprintf('%sUpload', $field));

        if (null === $path) {
            return;
        }

        $warmupCache = new WarmupCache($path, null, $overwrite);

        $this->messageBus->dispatch($warmupCache);
    }

    public function getImage(string $value, string $field, string $class): ?string
    {
        $object = new stdClass();
        $object->id = pathinfo($value, PATHINFO_FILENAME);
        $object->$field = $value;

        $path = $this->storage->resolveUri($object, sprintf('%sUpload', $field), $class);

        if (null === $path) {
            return null;
        }

        return $path;
    }

    /**
     * @throws Exception
     */
    public function getThumbnail(string $value, string $filter): string
    {
        $url = $this->filterService->getUrlOfFilteredImage($value, $filter);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $path) {
            throw new Exception(sprintf('Path not found: %s', $url));
        }

        return $path;
    }
}
