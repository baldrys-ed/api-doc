<?php

namespace Dev\ApiDocBundle\Describer;

use Dev\ApiDocBundle\Model\Operation;
use Dev\ApiDocBundle\Registry\RegistryInterface;
use ReflectionClass;
use ReflectionUnionType;
use function array_merge;

class OperationDescriber extends AbstractDescriber
{
    public function __construct(protected iterable $parsers, protected RegistryInterface $registry)
    {
        parent::__construct($parsers, $registry);
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    protected function getItemsToParse(string $class): iterable
    {
        $reflection = new ReflectionClass($class);

        $invoke = $reflection->getMethod('__invoke');
        $returnType = $invoke->getReturnType();

        $types = [];
        if ($returnType instanceof ReflectionUnionType) {
            foreach ($returnType->getTypes() as $type) {
                $types[] = new ReflectionClass($type->getName());
            }
        } elseif (class_exists($returnType)) {
            $types[] = new ReflectionClass($returnType->getName());
        }

        return array_merge($reflection->getAttributes(), $types);
    }

    protected function getModel(): string
    {
        return Operation::class;
    }
}