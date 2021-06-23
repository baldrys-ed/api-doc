<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Describer\ComponentDescriber;
use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\Property;
use Dev\ViewBundle\View\ConfigurableResponseInterface;
use Dev\ViewBundle\View\ViewInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use function in_array;

class ViewParser implements ComponentParserInterface
{
    public function __construct(private ComponentDescriber $describer, private PropertyParser $parser)
    {
    }

    public function supports(object $item): bool
    {
        return in_array(ViewInterface::class, $item->getInterfaceNames());
    }

    public function parse(Model $model, object $item): Model
    {
        /* @var Component $model */
        /* @var Component $child */
        /* @var ReflectionClass $item */

        $parameters = [];
        $status = Response::HTTP_OK;
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (in_array(ConfigurableResponseInterface::class, $item->getInterfaceNames())) {
            $instance = $item->newInstance();
            $status = $item->getMethod('getResponseStatus')->invoke($instance);
            $headers = $item->getMethod('getResponseHeaders')->invoke($instance);
        }

        foreach ($item->getProperties() as $property) {
            $propertyName = $property->getName();
            $propertyTypeName = $this->convertType($property->getType()->getName());
            try {
                $propertyType = new ReflectionClass($propertyTypeName);
                $child = $this->describer->describe($propertyTypeName);
                $child->parent = $model;
                $parameter = Property::factory($child->id, 'object');
                $parameter->ref = $child;
                // $parameters[] = $property;
            } catch (ReflectionException $e) {
                // $parameters[] = Property::factory($propertyName, $propertyTypeName);
                $parameter = Property::factory($propertyName, $propertyTypeName);
            }

            $parameter = $this->parser->parse($parameter, $property);
            $parameters[] = $parameter;
        }

        $model->id = $item->getShortName();

        if (null === $model->parent) {
            $model->status = $status;
            $model->headers = $headers;
        }

        $model->parameters = $parameters;

        return $model;
    }

    private function convertType(string $type): string
    {
        if ($type === 'int') {
            return 'integer';
        }
        if ($this === 'float') {
            return 'number';
        }

        return $type;
    }
}