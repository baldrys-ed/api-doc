<?php

namespace Dev\ApiDocBundle\Parser;

use Attribute;
use Dev\ApiDocBundle\Model\Model;
use Symfony\Component\Form\FormTypeInterface;

interface ParserInterface
{
    /**
     * @param Model  $model model to fill with data
     * @param Attribute|object|FormTypeInterface $item
     * @return Model
     */
    public function parse(Model $model, object $item): Model;
    public function supports(object $item): bool;
}