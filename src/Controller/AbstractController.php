<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

abstract class AbstractController extends BaseAbstractController
{
    public const GROUPS = [];

    public function getForm(string $type, mixed $object, ?array $data, callable $callback): JsonResponse
    {
        if (null === $data) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm($type, $object);
        $form->submit($data);

        if (false === $form->isValid()) {
            return new JsonResponse($form->getErrors(true), Response::HTTP_OK);
        }

        $data = $callback($form->getData());
        $data['status'] = 'OK';

        return new JsonResponse($data, Response::HTTP_CREATED);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function assertNotFound(?object $data, string $message): void
    {
        if (null === $data) {
            throw $this->createNotFoundException($message);
        }
    }

    protected function getItemResponse(object $data = null, array $groups = []): JsonResponse
    {
        if (null === $data) {
            $error = [
                sprintf('%s not found', ucfirst($groups[0])),
            ];

            return $this->json($error, Response::HTTP_NOT_FOUND, [], [
                AbstractNormalizer::GROUPS => $groups,
            ]);
        }

        return $this->json($data, Response::HTTP_OK, [], [
            AbstractNormalizer::GROUPS => $groups,
        ]);
    }

    protected function getListResponse(mixed $data, array $groups): JsonResponse
    {
        return $this->json($data, Response::HTTP_OK, [], [
            AbstractNormalizer::GROUPS => $groups,
        ]);
    }
}
