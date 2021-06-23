<?php

namespace Dev\ApiDocBundle\Model;

class ModelFactory
{
    public static function factory(string $class): Model
    {
        switch ($class) {
            case Component::class:
                return new Component();
            case Operation::class:
                return new Operation();
        }

        return new Model();
    }
}