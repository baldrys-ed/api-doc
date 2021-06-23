<?php

namespace Dev\ApiDocBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Property
{
    public function __construct(public ?bool $required = null, public array $attr = [])
    {
    }
}