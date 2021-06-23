<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Describer\ComponentDescriber;
use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use ReflectionClass;

class ResponseParser implements OperationParserInterface
{
    public function __construct(private ComponentDescriber $componentDescriber)
    {
    }

    public function supports(object $attribute): bool
    {
        return $attribute instanceof ReflectionClass;
    }

    public function parse(Model $model, object $reflection): Model
    {
        /* @var Component $response */
        $response = $this->componentDescriber->describe($reflection->getName());
        $model->responses[$response->status] = $response;

        return $model;
    }
}