<?php

namespace Dev\ApiDocBundle\Parser;

use Attribute;
use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\Operation;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Routing\Annotation\Route;
use Dev\ApiDocBundle\Attribute\Model as ModelAttribute;

class ModelParser implements OperationParserInterface
{
    public function supports(object $item): bool
    {
        /* @var \ReflectionAttribute $attribute */
        return $item->getName() === ModelAttribute::class;
    }

    public function parse(Model $model, object $item): Model
    {
        return $model;
    }
}