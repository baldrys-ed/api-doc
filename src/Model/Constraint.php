<?php

namespace Dev\ApiDocBundle\Model;

class Constraint
{
    public ?bool $nullable = null;
    public ?bool $readOnly = null;
    public ?bool $writeOnly = null;
    // string
    public ?int $minLength = null;
    public ?int $maxLength = null;
    public ?string $pattern = null;
    // integer
    public ?int $minimum = null;
    public ?int $maximum = null;
    // array
    public ?int $minItems = null;
    public ?int $maxItems = null;
    // object
    public ?int $minProperties = null;
    public ?int $maxProperties = null;
}