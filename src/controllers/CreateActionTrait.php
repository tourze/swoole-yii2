<?php

namespace tourze\swoole\yii2\controllers;

use ReflectionMethod;
use Yii;
use yii\base\Action;
use yii\base\InlineAction;

/**
 * 用于优化控制器的createAction方法
 * 在需要优化的控制器中use即可
 *
 * @package tourze\swoole\yii2\controllers
 */
trait CreateActionTrait
{

    /**
     * @var array
     */
    protected static $actionMapInstances = [];

    /**
     * Controller中的createAction方法, 每次都会从Yii::createObject或者Reflection创建对象
     * 这里会带来一部分额外开销, 现在在这里引入一些缓存, 减少高并发下的CPU压力
     *
     * @param string $id
     * @return bool|null|object|\yii\base\Action|\yii\base\InlineAction
     * @throws \yii\base\InvalidConfigException
     */
    public function createAction($id)
    {
        if ($id === '')
        {
            $id = $this->defaultAction;
        }

        $key = __CLASS__ . '::' . $id;
        //echo $key . "\n";
        if ( ! isset(self::$actionMapInstances[$key]))
        {
            $action = false;
            $actionMap = $this->actions();
            if (isset($actionMap[$id]))
            {
                $action = Yii::createObject($actionMap[$id], [$id, $this]);
            }
            elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id)
            {
                $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
                if (method_exists($this, $methodName))
                {
                    $method = new ReflectionMethod($this, $methodName);
                    if ($method->isPublic() && $method->getName() === $methodName)
                    {
                        // $method在同样传入时, 不会有变化, 所有下面没清空它
                        $action = new InlineAction($id, $this, $methodName);
                    }
                }
            }

            if ($action)
            {
                $action->id = null;
                $action->controller = null;
                self::$actionMapInstances[$key] = $action;
            }
            else
            {
                return null;
            }
        }

        /** @var Action $action */
        $action = clone self::$actionMapInstances[$key];
        $action->id = $id;
        $action->controller = $this;
        //echo spl_object_hash($action) . "\n";
        return $action;
    }
}
