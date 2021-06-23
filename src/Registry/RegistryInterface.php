<?php

namespace Dev\ApiDocBundle\Registry;

use Dev\ApiDocBundle\Model\Model;

interface RegistryInterface
{
    public function register(Model $model): Model;
}