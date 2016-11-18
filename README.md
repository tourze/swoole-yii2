# swoole-yii2

## 项目说明

公司有一些项目使用Yii2进行开发, 跑在php-fpm模式下, 高并发下的性能问题很严重.
上了apc等opcode cache后也没能很好解决这些问题, 于是尝试用swoole来解决.

在这个项目前, 已经尝试过使用 https://github.com/bixuehujin/blink

Blink的优点在于简单, 缺点也在于太简单. 如果是新项目, 并且需求清晰是只用于接口的话, 使用Blink会更好.

如果是继续使用Yii2这类全栈框架, 并希望有明显的性能提升, 可以尝试使用本项目来实现.

本项目目前还是在试验项目, 请谨慎在正式环境中使用.

如果对项目有任何建议, 或者使用过程中遇到问题, 可以发issue给我.

## 项目的意义

对现在的Web项目来说, 特别是对PHP项目来说, 遇到性能问题, 第一时间想到的解决方案就是加机器.

不是说加机器不能解决问题, 如果问题是多点部署可以解决的话, 加机器也是一个很粗暴和容易实现的方案.

只是我认为, 加机器这种途径, 是属于运维范畴的解决方法. 对于开发者来说, 依然需要想办法来提升代码质量和效率.

## 适用人群

首先使用者应该对swoole有一定理解.
建议使用本项目前, 先将 http://wiki.swoole.com/ 中的说明阅读一次.

其次, 对于Yii2的核心概念和实现, 也应该有一定掌握.

## 已经完成的工作

* Http Server 的实现
* Request 组件的兼容处理
* Response 组件的兼容处理
* Session 组件的兼容处理
* 增加异步任务助手类
* Debug 模块的兼容处理
* Container 支持实例持久化
* Db 组件的自动重连
* 压力测试文档

## 进行中的工作

* swoole 任务投递的优化
* 增加单元测试
* swoole 管理脚本的完善

## 使用方法

首先执行 `composer require tourze/swoole-yii2`

下面的配置描述, 基本上就是基于 https://github.com/yiisoft/yii2-app-advanced 这个官方 DEMO 来说明的.
建议在阅读前先大概了解下这个项目.

### console配置

swoole 的实现, 全部都是基于 CLI 的, 项目的所有管理相关也是使用CLI实现的.

这里第一步我们应该先配置好一个 Yii2 的 console 服务.

在 `console/config/main.php` 中加入类似以下的代码:

```
    'id' => 'app-console',
    'controllerNamespace' => 'console\controllers',
    'controllerMap' => [
        // 在下面指定定义command控制器
        'swoole' => \tourze\swoole\yii2\commands\SwooleController::className(),
    ],
```

此时执行 `./yii`, 应该可以在底部看到 swoole 相关命令.

### frontend/backend配置

我们建议 frontend 部分使用 swoole 来运行, backend 部分依然使用已有的 php-fpm 模式来运行.

使用本项目, frontend 和 backend 的运行方式会有所变更.

在以前的方式中, 我们会在入口文件 include 所有配置, 然后 new Application 使系统运行起来.
在现在的新方式中, 我们的配置会在服务运行起来时就加载到内存, 节省了上面加载配置的时间.

我们需要在 `console/config/params.php` 中加入类似以下的代码:

```
<?php
return [
    'swooleHttp' => [
        'frontend' => [
            'host' => '127.0.0.1',
            'port' => '6677',
            'root' => realpath(__DIR__ . '/../../frontend/web'),
            // 在这里定义一些常用的可以常驻与内存的组件
            'persistClasses' => [
                'dmstr\web\AdminLteAsset',
                'dmstr\widgets\Alert',
                'kartik\grid\ActionColumn',
                'kartik\grid\ActionColumnAsset',
                'kartik\grid\BooleanColumn',
                'kartik\grid\CheckboxColumn',
                'kartik\grid\CheckboxColumnAsset',
                'kartik\grid\DataColumn',
                'kartik\grid\GridView',
                'kartik\grid\GridViewAsset',
                'kartik\grid\GridExportAsset',
                'kartik\grid\GridResizeColumnsAsset',
            ],
            // bootstrap文件, 只会引入一次
            'bootstrapFile' => [
                __DIR__ . '/../../common/config/aliases.php',
                __DIR__ . '/../../admin/config/aliases.php',
            ],
            // Yii的配置文件, 只会引入一次
            'configFile' => [
                __DIR__ . '/../../common/config/main.php',
                __DIR__ . '/../../frontend/config/main.php'
            ],
            // 有一些模块比较特殊, 无法实现Refreshable接口, 此时唯有在这里指定他的类名
            'bootstrapRefresh' => [
                'xxx\backend\Bootstrap',
            ],
            // 配置参考 https://www.kancloud.cn/admins/swoole/201155
            'server' => [
                'worker_num' => 20,
                'max_request' => 10000,
                'task_worker_num' => 50,  // 任务进程数
                'buffer_output_size' => 16 * 1024 * 1024, // 该参数可选, 如果你的业务需要输出大文件(如巨型html或导出大文件), 具体参考 http://wiki.swoole.com/wiki/page/440.html
            ],
        ],
    ],
];
```

配置好后, 我们执行 `./yii swoole/http frontend`, 就可以启动 swoole 服务器了.

## 兼容思路

在确定使用swoole来优化Yii2性能前, 我们先确定下目标:

1. Yii2项目能平滑从php-fpm环境迁移到swoole上
2. 代码尽量少侵入性, 最好做好零侵入性
3. 性能要有明显的提升(5倍以上)

逐点分析:

* 对于第1点, 如果项目之前运行在 php-fpm 模式中, 现在只要在 swoole 中模拟实现一次 php-fpm 的转发, 是不是就可以解决平滑迁移的问题?
* 对于第2点, Yii2 的组件系统, 已经给我们提供了一个很好的解决方案. 但是有一个问题需要考虑, 就是Yii中多少使用到了全局变量, 部分类使用了 static private 属性, 在组件兼容时, 为了避免踩坑, 可能需要将组件实现都review一次, 这里的时间成本不好控制
* 对于第3点, 我们可以先分析Yii2在执行时, 性能卡在哪里. 直观上来说, Yii2的项目, 性能应该只卡在文件IO和DI部分. 但从实际业务来说, 也可能会卡在网络IO或其他复杂的业务逻辑上. 所以也需要为这些问题提供一定解决方法才可以真实提升性能.

就上面的分析之后, 我尝试了三种方式来实现Yii2在swoole上的兼容:

### 模拟PHP-FPM, 直接执行PHP文件

这种实现思路最简单, 流程大概是:

1. 获取header信息, 将header信息和环境变量直接写入 `$_SERVER` 等全局变量中去
2. 直接include当前请求的文件

这种方式有一个很明显的优点, 那就是实现简单. 只需要跑起一个HttpServer, 然后就可以直接使用了, 而且通用性较强, 大部分PHP项目都可以直接跑.

但是缺点也十分明显, 这种方法只可以节省部分文件IO(如类的自动加载部分), 但是对于具体业务逻辑的实现, 没节省到任何时间.

以Yii2为例, 按照这种模式, 每次有请求进入, 打包好 `$_SERVER` 信息, 直接加载 `index.php` 即可, 然后在入口文件里面, 依然会继续初始化 Application, 再执行一大堆组件初始化操作.
而这些大部分可以复用的组件, 在当前请求结束后(也就是 swoole 的当前 Request 请求结束后)会自动销毁.

这种方案, 有反复的创建/销毁对象带来的开销, 而且每次请求都需要重新加载缺省文件, 带来一个没必要的文件读操作, 所以不一定是好方案.

### 唯一 Application 实例方式

上面的第一个方案, 是最早时尝试的, 目标有点贪心, 希望能在兼容 Yii2 时还能直接兼容其他 PHP 项目. 不过实践后发现目标定得有点大了.

第二种方式的目标是只兼容 Yii2, 思路是: 在 swoole 的 workerStart 时就创建一个 Yii2 应用对象, 然后每次请求进入后, 直接 handleRequest.

这种方式有什么问题? 直觉上来看没什么大问题, 减少了缺省文件的载入IO, 减少了重复创建对象和销毁对象的开销.

但是有一个很严重的问题, 就是 Yii2 的上下文混乱问题.

举个例子, 按照这种思路, Application 在 worker 内是唯一的, 那么 Response 组件也应该是唯一的. 

假如第一个请求输出头部信息:

```
Content-Type: application/json
Access-Control-Allow-Origin: *
```

第二个请求在执行时, 期望输出:

```
Content-Type:text/html; charset=utf-8
```

但实际输出的header信息却是:

```
Content-Type:text/html; charset=utf-8
Access-Control-Allow-Origin: *
```

因为 Response 组件是唯一的, 所以上一个返回的 headers 会一直存在在内存中, 第二个请求只会覆盖上一次请求的同名头部信息, 然后将常驻的头部信息全部输出!

类似的上下文问题还有 `\yii\base\Widget::$counter` 和 `\yii\base\Widget::$stack`, etc.

所以这种方式, 效率提升比第一种方式要明显, 但是带来的问题也更多.

### 实例复制方式(最终选择)

这种方式是在第二种方式的基础上来实现的. 主要思路是:

1. 每个 worker 进程都创建一个 Application 对象
2. 每个请求处理时, clone 一个 Application 对象
3. 将一些不用重复构建的组件, 同样 clone 到刚复制出来的 Application 对象
4. 然后在这个复制出来的对象中运行原有逻辑

这种方案的优点是减少了主要组件的构建开销, 同时整个应用的执行流程也清晰很多了.
组件的上下文问题变成了可以控制的复制与否问题.

现在本项目就是使用这种方式来实现的.

上面的思路, 很多并不只是针对 Yii2, 使用同样思路, 同样可以顺利改造其他现代化框架, 如 Symfony.

关于上面几种实现方式, 如果你有更好的方法, 我们可以一起沟通下.

## [压测结果](/STRESS_TESTING.md)
