<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\Operation;
use Symfony\Component\Routing\Annotation\Route;

class RouteParser implements OperationParserInterface
{
    public function supports(object $attribute): bool
    {
        return $attribute->getName() === Route::class;
    }

    public function parse(Model $model, object $attribute): Model
    {
        /* @var Route $instance */
        /* @var Operation $model */
        $instance = $attribute->newInstance();
        $model->id =  $instance->getName();
        $model->path = $instance->getPath();
        // Передается массив методов
        $model->method = $instance->getMethods()[0];

        return $model;
    }
}