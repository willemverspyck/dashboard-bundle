<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Normalizer;

use Spyck\DashboardBundle\Parameter\EntityParameterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer as BaseAbstractNormalizer;

final class ParameterNormalizer extends AbstractNormalizer
{
    /**
     * {@inheritDoc}
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $context[BaseAbstractNormalizer::GROUPS][] = $object->getName();

        return $this->normalizer->normalize($object->getDataAsObject(), $format, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof EntityParameterInterface;
    }
}
