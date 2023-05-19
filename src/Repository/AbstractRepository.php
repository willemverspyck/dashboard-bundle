<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * Truncate field if the length is longer than the field can handle in database.
     */
    protected function truncate(string $className, string $field, ?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $metaData = $this->getEntityManager()->getClassMetadata($className);

        if (array_key_exists($field, $metaData->fieldMappings)) {
            $length = $metaData->fieldMappings[$field]['length'];

            if (mb_strlen($value) > $length) {
                $value = mb_substr($value, 0, $length);
            }
        }

        return $value;
    }
}
