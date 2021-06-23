<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Model\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SecurityParser implements OperationParserInterface
{
    public function supports(object $attribute): bool
    {
        return $attribute->getName() === Security::class || $attribute->getName() === IsGranted::class;
    }

    public function parse(Model $model, object $attribute): Model
    {
        if (empty($model->security)) {
            $model->security['default'] = [];
        }

        return $model;
    }
}