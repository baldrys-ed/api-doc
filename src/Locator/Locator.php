<?php

namespace Dev\ApiDocBundle\Locator;

use Dev\ApiDocBundle\Attribute\Operation;
use Dev\ApiDocBundle\Utils\ClassParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Routing\Annotation\Route;

class Locator
{
    public function __construct()
    {
    }

    public function locate(string $srcPath): iterable
    {
        return $this->getClasses($srcPath);
    }

    private function getClasses(string $srcPath): iterable
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath));

        $classes = [];
        $classParser = new ClassParser();
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            if ($class = $classParser->extractClass($file->getPathname())) {
                try {
                    $reflectionClass = new ReflectionClass($class);
                } catch (ReflectionException $e) {
                    continue;
                }
                if ($this->containsAttribute($reflectionClass, Operation::class) && $this->containsAttribute($reflectionClass, Route::class)) {
                    $classes[] = $class;
                }
            }
        }


        return $classes;
    }

    private function containsAttribute(ReflectionClass $reflectionClass, string $attributeName)
    {
        $attributes = $reflectionClass->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === $attributeName) {
                return true;
            }
        }

        return false;
    }
}