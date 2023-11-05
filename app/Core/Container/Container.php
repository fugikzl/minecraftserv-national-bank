<?php

namespace App\Core\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Container implements ContainerInterface
{
    private array $entries = [];

    public function get(string $class)
    {
        if(!$this->has($class)){
            return $this->resolve($class);
        }

        $entry = $this->entries[$class];
        
        if(is_object($entry)){
            return $entry;
        }

        return $entry();
    }

    public function has(string $class) : bool
    {
        return isset($this->entries[$class]);
    }

    public function set(string $class, callable $concrete) : void
    {
        $this->entries[$class] = $concrete;
    }

    public function setSingletone(string $class, callable $concrete) : void
    {
        $this->entries[$class] = $concrete();
    }

    public function resolve(string $class)      
    {
        $reflectionClass = new ReflectionClass($class);

        if(!$reflectionClass->isInstantiable()){
            throw new ContainerException("Class " . $class . " is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();
        
        if(!$constructor){
            return new $class;
        }

        $constructorParams = $constructor->getParameters();

        if(!$constructorParams){
            return new $class();
        }

        $dependencies = array_map(function(ReflectionParameter $parameter) use ($class){
            $name = $parameter->getName();
            $type = $parameter->getType();

            if(!$type){
                throw new ContainerException("Can;t reslove untyped parameter " . $name);
            }

            if($type instanceof ReflectionUnionType){
                throw new ContainerException("Can't resolve union types");
            }

            if($type instanceof ReflectionNamedType && !$type->isBuiltin()){
                return $this->get($type->getName());
            }

            throw new ContainerException("Can;t reslove parameter " . $name);

        }, $constructorParams);

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}