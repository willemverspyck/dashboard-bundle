<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Schema;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class BadRequest extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: HttpFoundationResponse::HTTP_BAD_REQUEST,
            description: 'Bad request',
            content: new JsonContent(type: 'array', items: new Items(properties: [
                new Property(property: 'field', type: 'string'),
                new Property(property: 'message', type: 'string'),
            ], type: 'object')),
        );
    }
}
