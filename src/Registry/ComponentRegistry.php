<?php

namespace Dev\ApiDocBundle\Registry;

use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Property;

class ComponentRegistry extends Registry
{
    public function toArray(): array
    {
        $data = [];
        $components = $this->models;

        /* @var Component $component */
        /* @var Property $property */
        foreach ($components as $component) {
            $componentData = [];
            $componentData['properties'] = [];
            $properties = $component->parameters;
            foreach ($properties as $property) {
                $componentData['properties'][$property->name] = $this->propertyToArray($property);
            }
            if (!empty($required = $component->required)) {
                $componentData['required'] = $required;
            }
            $data[$component->id] = $componentData;
        }

        return [
            'schemas' => $data,
        ];
    }

    private function propertyToArray(Property $property): array
    {
        $data = [];

        if (null !== $required = $property->required) {
            $data['required'] = $required;
        }

        if (null !== $ref = $property->ref) {
            $data['$ref'] = '#/components/schemas/'.$ref->id;
        } else {
            $data['type'] = $property->type;
        }

        if (null !== $format = $property->format) {
            $data['format'] = $format;
        }

        if (!empty($items = $property->items)) {
            $data['items'] = $this->propertyToArray($items);
        }

        if (!empty($enum = $property->enum)) {
            $data['enum'] = $enum;
        }

        if (!empty($property->properties)) {
            foreach ($property->properties as $subProperty) {
                $data['properties'][$subProperty->name] = $this->propertyToArray($subProperty);
            }
        }

        $data += $property->attributes;

        return $data;
    }
}