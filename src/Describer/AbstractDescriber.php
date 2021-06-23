<?php

namespace Dev\ApiDocBundle\Describer;

use Dev\ApiDocBundle\Exception\DescriberException;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\ModelFactory;
use Dev\ApiDocBundle\Parser\ParserInterface;
use Dev\ApiDocBundle\Registry\RegistryInterface;

abstract class AbstractDescriber implements DescriberInterface
{
    public function __construct(protected iterable $parsers, protected RegistryInterface $registry)
    {
    }

    protected abstract function getItemsToParse(string $class): iterable;

    protected abstract function getModel(): string;

    public function describe(string $class): Model
    {
        $items = $this->getItemsToParse($class);

        $model = ModelFactory::factory($this->getModel());

        /* @var ParserInterface $parser */
        foreach ($items as $item) {
            foreach ($this->parsers as $parser) {
                if ($parser->supports($item)) {
                    $model = $parser->parse($model, $item);
                }
            }
        }

        if (null === $model->id){
            throw new DescriberException('No parsers was found for $class '.$class);
        }

        $this->registry->register($model);

        return $model;
    }
}