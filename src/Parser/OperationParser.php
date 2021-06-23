<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Attribute\Operation;
use Dev\ApiDocBundle\Describer\ComponentDescriber;
use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\ModelFactory;
use Dev\ApiDocBundle\Model\Operation as OperationModel;
use ReflectionAttribute;
use function array_merge;

class OperationParser implements OperationParserInterface
{
    public function __construct(private ComponentDescriber $componentDescriber)
    {
    }

    public function supports(object $attribute): bool
    {
        /* @var ReflectionAttribute $attribute */
        return $attribute->getName() === Operation::class;
    }

    public function parse(Model $model, object $attribute): Model
    {
        /* @var OperationModel $model */
        /* @var ReflectionAttribute $attribute */
        $instance = $attribute->newInstance();
        $model->description = $instance->description;

        $request = $instance->request;
        $requestModel = null;
        if (null !== $request) {
            if (class_exists($request)) {
                $requestModel = $this->componentDescriber->describe($request);
            } else {
                $requestModel = ModelFactory::factory(Component::class);
                $requestModel->id = $request;
            }
        }

        if (!empty($security = $instance->security)) {
            $modelSecurity = $model->security;
            if (isset($modelSecurity['default'])) {
                foreach ($security as $key => $value) {
                    $security[$key] = array_merge($value, $modelSecurity['default']);
                }
            }
            $model->security = $security;
        }

        $model->responses += $instance->responses;
        $model->request = $requestModel;

        return $model;
    }
}