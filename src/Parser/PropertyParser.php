<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Model\Property;
use ReflectionProperty;

class PropertyParser
{
    public function parse(Property $property, ReflectionProperty $reflection): Property
    {
        $attributes = $reflection->getAttributes();
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof \Dev\ApiDocBundle\Attribute\Property) {
                $property->required = $instance->required;
                $property->attributes += $instance->attr;
            }
        }

        return $property;
    }
}