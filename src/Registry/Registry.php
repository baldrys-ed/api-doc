<?php

namespace Dev\ApiDocBundle\Registry;

use Dev\ApiDocBundle\Model\Model;

abstract class Registry implements RegistryInterface
{
    protected iterable $models = [];

    public function register(Model $model): Model
    {
        $id = $model->id;
        if (!isset($this->models[$id])) {
            $this->models[$id] = $model;
        }

        return $model;
    }
}