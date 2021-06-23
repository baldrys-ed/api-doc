<?php

namespace Dev\ApiDocBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Model
{
    public function __construct(public ?string $model = null, public ?string $type = null)
    {
    }
}