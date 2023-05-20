<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle\Filter;

final class OptionFilter extends AbstractOptionFilter
{
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
        $this->setType(FilterInterface::TYPE_CHECKBOX);
    }

    public function getField(): string
    {
        return 'options';
    }

    public function getName(): string
    {
        return 'option';
    }
}
