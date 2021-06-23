<?php

namespace Dev\ApiDocBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Operation
{
    public function __construct(public ?string $description = null, public ?string $request = null, public array $responses = [], public array $security = [])
    {
    }
}