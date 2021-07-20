<?php

namespace Dev\ApiDocBundle\Parser;

use Dev\ApiDocBundle\Describer\ComponentDescriber;
use Dev\ApiDocBundle\Model\Component;
use Dev\ApiDocBundle\Model\Model;
use Dev\ApiDocBundle\Model\Property;
use ReflectionClass;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Validator\Constraint as FormConstraint;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;
use function in_array;

class FormParser implements ComponentParserInterface
{
    public function __construct(private FormFactoryInterface $formFactory, private ComponentDescriber $describer)
    {
    }

    public function supports(object $item): bool
    {
        return in_array(FormTypeInterface::class, $item->getInterfaceNames());
    }

    public function parse(Model $model, object $item): Model
    {
        /* @var ReflectionClass $item */
        /* @var Component $model */
        /* @var Component $childComponent */
        $form = $this->formFactory->create($item->getName());

        $model->id = $item->getShortName();
        $required = [];
        foreach ($form as $name => $child) {
            $config = $child->getConfig();

            if ($config->getRequired()) {
                $required[] = $name;
            }

            $property = Property::factory($name);
            $model->parameters[] = $property;
            $this->findFormType($property, $config);
        }

        $model->required = $required;

        return $model;
    }

    private function findConstraint(array $constraints, string $name): ?FormConstraint
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof $name) {
                return $constraint;
            }
        }

        return null;
    }

    private function findFormType(Property $property, $config): void
    {
        /* @var Component $model */
        /* @var Component $childComponent */
        $type = $config->getType();
        $constraints = $config->getOption('constraints');

        if (!$builtinFormType = $this->getBuiltinFormType($type)) {
            $childComponent = $this->describer->describe(get_class($type->getInnerType()));
            $property->ref = $childComponent;
            $childComponent->parent = $property;

            return;
        }

        do {
            $blockPrefix = $builtinFormType->getBlockPrefix();

            if ('text' === $blockPrefix) {
                $property->type = 'string';

                if ($constraint = $this->findConstraint($constraints, Length::class)) {
                    if (null !== $constraint->min){
                        $property->attributes['minLength'] = $constraint->min;
                    }
                    if (null !== $constraint->max){
                        $property->attributes['maxLength'] = $constraint->max;
                    }
                }

                break;
            }

            if ('number' === $blockPrefix || 'integer' === $blockPrefix) {
                $property->type = 'number' === $blockPrefix ? 'number' : 'integer';

                if ($constraint =
                    $this->findConstraint($constraints, GreaterThan::class) ?? $this->findConstraint($constraints, GreaterThanOrEqual::class)) {
                    $property->attributes['minimum'] = $constraint->value;
                }
                if ($constraint =
                    $this->findConstraint($constraints, LessThan::class) ?? $this->findConstraint($constraints, LessThanOrEqual::class)) {
                    $property->attributes['maximum'] = $constraint->value;
                }

                if ($constraint = $this->findConstraint($constraints, Range::class)) {
                    if (null !== $constraint->min){
                        $property->attributes['minimum'] = $constraint->min;
                    }
                    if (null !== $constraint->max){
                        $property->attributes['maximum'] = $constraint->max;
                    }
                }

                break;
            }

            if ('file' === $blockPrefix) {
                $property->type = 'string';

                break;
            }

            if ('date' === $blockPrefix) {
                $property->type = 'string';
                $property->format = 'date';

                break;
            }

            if ('datetime' === $blockPrefix) {
                $property->type = 'string';
                $property->format = 'date-time';

                break;
            }

            if ('checkbox' === $blockPrefix) {
                $property->type = 'boolean';

                break;
            }

            if ('password' === $blockPrefix) {
                $property->type = 'string';
                $property->format = 'password';

                break;
            }

            if ('repeated' === $blockPrefix) {
                $property->type = 'object';
                $required = [$config->getOption('first_name'), $config->getOption('second_name')];
                $property->required = $required;

                $subType = $config->getOption('type');

                foreach (['first', 'second'] as $subField) {
                    $subName = $config->getOption($subField.'_name');
                    $subForm = $this->formFactory->create(
                        $subType,
                        null,
                        array_merge(
                            $config->getOption('options'),
                            $config->getOption($subField.'_options')
                        )
                    );
                    $subProperty = Property::factory($subName);
                    $property->properties[] = $subProperty;
                    $this->findFormType($subProperty, $subForm->getConfig());
                }

                break;
            }

            if ('choice' === $blockPrefix) {
                if ($config->getOption('multiple')) {
                    $property->type = 'array';
                } else {
                    $property->type = 'string';
                }
                if (($choices = $config->getOption('choices')) && is_array($choices) && count($choices)) {
                    $enums = array_values($choices);
                    if ($this->isNumbersArray($enums)) {
                        $type = 'number';
                    } elseif ($this->isBooleansArray($enums)) {
                        $type = 'boolean';
                    } else {
                        $type = 'string';
                    }

                    if ($config->getOption('multiple')) {
                        // Какое имя
                        $subProperty = Property::factory('items', $type);
                        $subProperty->enum = $enums;
                        $property->items = $subProperty;
                    } else {
                        $property->type = $type;
                        $property->enum = $enums;
                    }
                }

                break;
            }

            if ('collection' === $blockPrefix) {
                $subType = $config->getOption('entry_type');
                $subOptions = $config->getOption('entry_options');
                $subForm = $this->formFactory->create($subType, null, $subOptions);

                $property->type = 'array';
                $subProperty = Property::factory('items', $subType);
                $property->items = $subProperty;

                $this->findFormType($subProperty, $subForm->getConfig());

                break;
            }

            if ('entity' === $blockPrefix || 'document' === $blockPrefix) {
                $entityClass = $config->getOption('class');

                if ($config->getOption('multiple')) {
                    $property->format = sprintf('[%s id]', $entityClass);
                    $property->type = 'array';
                    $subProperty = Property::factory('items', 'string');
                    $property->items = $subProperty;
                } else {
                    $property->type = 'string';
                    $property->format = sprintf('%s id', $entityClass);
                }

                break;
            }
        } while ($builtinFormType = $builtinFormType->getParent());
    }

    /**
     * @return ResolvedFormTypeInterface|null
     */
    private function getBuiltinFormType(ResolvedFormTypeInterface $type)
    {
        do {
            $class = get_class($type->getInnerType());

            if (FormType::class === $class) {
                return null;
            }

            if ('entity' === $type->getBlockPrefix() || 'document' === $type->getBlockPrefix()) {
                return $type;
            }

            if (0 === strpos($class, 'Symfony\Component\Form\Extension\Core\Type\\')) {
                return $type;
            }
        } while ($type = $type->getParent());

        return null;
    }

    /**
     * @return bool true if $array contains only numbers, false otherwise
     */
    private function isNumbersArray(array $array): bool
    {
        foreach ($array as $item) {
            if (!is_numeric($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool true if $array contains only booleans, false otherwise
     */
    private function isBooleansArray(array $array): bool
    {
        foreach ($array as $item) {
            if (!is_bool($item)) {
                return false;
            }
        }

        return true;
    }
}