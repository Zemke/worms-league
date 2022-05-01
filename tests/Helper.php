<?php

namespace App\Tests;

class Helper
{
    // TODO migrate all mocks to this solution of setting the id
    public static function setId(object $entity, int $value): object
    {
        $class = new \ReflectionClass($entity);
        $property = $class->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $value);
        return $entity;
    }
}

