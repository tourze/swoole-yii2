<?php

namespace tourze\swoole\yii2;

use ReflectionClass;
use yii\base\Object;
use yii\di\NotInstantiableException;

class Container extends \yii\di\Container
{

    /**
     * @var array
     */
    public static $persistClasses = [];

    /**
     * @var array
     */
    public static $persistInstances = [];

    /**
     * @inheritdoc
     */
    protected function build($class, $params, $config)
    {
        echo $class . "\n";
        return parent::build($class, $params, $config);
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($params as $index => $param)
        {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if ( ! $reflection->isInstantiable())
        {
            throw new NotInstantiableException($reflection->name);
        }
        if (empty($config))
        {
            return $reflection->newInstanceArgs($dependencies);
        }

        if ( ! empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable'))
        {
            // 如果类可以持久化, 则有另外的逻辑
            // 暂时只支持在构造函数中传$config来赋值的类
            if (in_array($class, self::$persistClasses))
            {
                if ( ! isset(self::$persistInstances[$class]))
                {
                    // 构造对象, 并且跳过构造器
                    self::$persistInstances[$class] = $reflection->newInstanceWithoutConstructor();
                }
                $object = clone self::$persistInstances[$class];
                foreach ($config as $name => $value)
                {
                    $object->$name = $value;
                }
                if ($object instanceof Object)
                {
                    // 触发一次init初始化流程
                    $object->init();
                }
                return $object;
            }
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            //var_dump($reflection, $dependencies);
            //echo $class . "<br/>\n";
            return $reflection->newInstanceArgs($dependencies);
        }
        else
        {
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value)
            {
                $object->$name = $value;
            }
            return $object;
        }
    }
}
