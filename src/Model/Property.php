<?php

namespace Dev\ApiDocBundle\Model;

/**
 * Class Property
 * @example
 *       id:
 *          type: integer
 * @package Dev\ApiDocBundle\Model
 */
class Property
{
    public ?string $name = null;
    public ?string $type = null;
    public ?string $format = null;
    public array $properties = [];
    public $required = null;
    public ?Component $ref = null;
    public ?Property $items = null;
    public array $enum = [];
    public array $attributes = [];

    public static function factory(string $name, ?string $type = null, ?string $format = null): self
    {
        $property = new self();
        $property->name = $name;
        $property->type = $type;
        $property->format = $format;

        return $property;
    }
}