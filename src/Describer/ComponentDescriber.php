<?php

namespace Dev\ApiDocBundle\Describer;

use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Registry\ComponentRegistry;
use Dev\ApiDocBundle\Registry\RegistryInterface;
use ReflectionClass;

class ComponentDescriber extends AbstractDescriber
{
    public function __construct(protected iterable $parsers, protected RegistryInterface $registry)
    {
        $this->registry = new ComponentRegistry();
        parent::__construct($parsers, $registry);
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    public function describe(string $class): Model
    {
        return parent::describe($class);
    }

    protected function getItemsToParse(string $class): iterable
    {
        $reflection = new ReflectionClass($class);

        return [$reflection];
    }

    protected function getModel(): string
    {
        return Component::class;
    }
}