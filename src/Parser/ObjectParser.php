<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\Property;
use Dev\ViewBundle\View\ViewInterface;
use ReflectionClass;
use Symfony\Component\Form\FormTypeInterface;
use function in_array;

class ObjectParser implements ComponentParserInterface
{
    public function __construct(private PropertyParser $parser)
    {
    }

    /**
     * Checks if the object has no interfaces and parent
     * @param object $attribute
     * @return bool
     */
    public function supports(object $item): bool
    {
        return !in_array(ViewInterface::class, $item->getInterfaceNames())
               && !in_array(FormTypeInterface::class, $item->getInterfaceNames())
               && $item->getName() !== ViewInterface::class;
    }

    public function parse(Model $model, object $reflection): Model
    {
        /* @var Component $model */
        /* @var ReflectionClass $reflection */
        $name = $reflection->getShortName();
        $model->id = $name;
        $parameters = [];

        // Без рекурсии, не ожидается вложенных Dto
        foreach ($reflection->getProperties() as $property) {
            $parameter = Property::factory($property->getName(), $this->convertType($property->getType()));
            $parameter = $this->parser->parse($parameter, $property);
            $parameters[] = $parameter;
        }

        $model->parameters = $parameters;

        return $model;
    }

    private function convertType(string $type): string
    {
        if ($type === 'int' || $type === '?int') {
            return 'integer';
        }
        if ($type === 'float' || $type === '?float') {
            return 'number';
        }
        if ($type === '?string' || $type === 'string') {
            return 'string';
        }

        return $type;
    }
}