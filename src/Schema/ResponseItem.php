<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Schema;

use Attribute;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ResponseItem extends Response
{
    public function __construct(string $type, array $groups = null)
    {
        parent::__construct(
            response: HttpFoundationResponse::HTTP_OK,
            description: 'Response item',
            content: new JsonContent(
                ref: new Model(
                    type: $type,
                    groups: $groups,
                ),
            ),
        );
    }
}
