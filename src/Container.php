<?php

namespace tourze\swoole\yii2;

use ReflectionClass;
use Yii;
use yii\base\Object;

/**
 * 继承原有的容器, 实现一些额外的逻辑
 *
 * @package tourze\swoole\yii2
 */
class Container extends \yii\di\Container
{

    /**
     * @var array 类的别名
     */
    public static $classAlias = [
        'yii\db\Command' => 'tourze\swoole\yii2\db\Command',
        'yii\db\Connection' => 'tourze\swoole\yii2\db\Connection',
        'yii\debug\Module' => 'tourze\swoole\yii2\debug\Module',
        'yii\debug\panels\ConfigPanel' => 'tourze\swoole\yii2\debug\ConfigPanel',
        'yii\debug\panels\RequestPanel' => 'tourze\swoole\yii2\debug\RequestPanel',
        'yii\log\Dispatcher' => 'tourze\swoole\yii2\log\Dispatcher',
        'yii\log\FileTarget' => 'tourze\swoole\yii2\log\FileTarget',
        'yii\log\Logger' => 'tourze\swoole\yii2\log\Logger',
        'yii\swiftmailer\Mailer' => 'tourze\swoole\yii2\mailer\SwiftMailer',
        'yii\web\Request' => 'tourze\swoole\yii2\web\Request',
        'yii\web\Response' => 'tourze\swoole\yii2\web\Response',
        'yii\web\Session' => 'tourze\swoole\yii2\web\Session',
        'yii\web\AssetManager' => 'tourze\swoole\yii2\web\AssetManager',
        'yii\web\ErrorHandler' => 'tourze\swoole\yii2\web\ErrorHandler',
        'yii\web\User' => 'tourze\swoole\yii2\web\User',
        'yii\web\View' => 'tourze\swoole\yii2\web\View',
    ];

    /**
     * @var array 需要持久化的类
     */
    public static $persistClasses = [
        'yii\base\ActionFilter',
        'yii\base\ModelEvent',
        'yii\base\Security',
        'yii\base\Theme',
        'yii\base\ViewEvent',
        'yii\behaviors\AttributeBehavior',
        'yii\behaviors\AttributeTypecastBehavior',
        'yii\behaviors\BlameableBehavior',
        'yii\behaviors\SluggableBehavior',
        'yii\behaviors\TimestampBehavior',
        'yii\bootstrap\Alert',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\bootstrap\Button',
        'yii\bootstrap\ButtonDropdown',
        'yii\bootstrap\ButtonGroup',
        'yii\bootstrap\Carousel',
        'yii\bootstrap\Collapse',
        'yii\bootstrap\Dropdown',
        'yii\bootstrap\InputWidget',
        'yii\bootstrap\Modal',
        'yii\bootstrap\Nav',
        'yii\bootstrap\NavBar',
        'yii\bootstrap\Progress',
        'yii\bootstrap\Tabs',
        'yii\bootstrap\ToggleButtonGroup',
        'yii\bootstrap\Widget',
        'yii\caching\ApcCache',
        'yii\caching\ArrayCache',
        'yii\caching\DbCache',
        'yii\caching\DummyCache',
        'yii\caching\FileCache',
        'yii\caching\MemCache',
        'yii\caching\MemCacheServer',
        'yii\caching\WinCache',
        'yii\caching\XCache',
        'yii\caching\ZendDataCache',
        'yii\captcha\Captcha',
        'yii\captcha\CaptchaAsset',
        'yii\captcha\CaptchaValidator',
        'yii\data\ActiveDataProvider',
        'yii\data\ArrayDataProvider',
        'yii\data\Pagination',
        'yii\data\Sort',
        'yii\data\SqlDataProvider',
        'yii\db\ActiveQuery',
        'yii\db\ColumnSchema',
        'yii\db\Command',
        'yii\db\Migration',
        'yii\db\mysql\Schema',
        'yii\db\Query',
        'yii\db\TableSchema',
        'yii\db\Transaction',
        'yii\debug\components\search\Filter',
        'yii\debug\components\search\matchers\GreaterThan',
        'yii\debug\components\search\matchers\LowerThan',
        'yii\debug\components\search\matchers\SameAs',
        'yii\debug\models\search\Db',
        'yii\debug\models\search\Debug',
        'yii\debug\models\search\Log',
        'yii\debug\models\search\Mail',
        'yii\debug\models\search\Profile',
        'yii\debug\panels\AssetPanel',
        'yii\debug\panels\DbPanel',
        'yii\debug\panels\LogPanel',
        'yii\debug\panels\MailPanel',
        'yii\debug\panels\ProfilingPanel',
        'yii\filters\AccessControl',
        'yii\filters\AccessRule',
        'yii\filters\auth\CompositeAuth',
        'yii\filters\auth\HttpBasicAuth',
        'yii\filters\auth\HttpBearerAuth',
        'yii\filters\auth\QueryParamAuth',
        'yii\filters\ContentNegotiator',
        'yii\filters\Cors',
        'yii\filters\HttpCache',
        'yii\filters\PageCache',
        'yii\filters\RateLimiter',
        'yii\filters\VerbFilter',
        'yii\grid\ActionColumn',
        'yii\grid\CheckboxColumn',
        'yii\grid\DataColumn',
        'yii\grid\GridView',
        'yii\grid\GridViewAsset',
        'yii\grid\SerialColumn',
        'yii\i18n\Formatter',
        'yii\i18n\DbMessageSource',
        'yii\i18n\Formatter',
        'yii\i18n\GettextMessageSource',
        'yii\i18n\GettextMoFile',
        'yii\i18n\GettextPoFile',
        'yii\i18n\I18N',
        'yii\i18n\MessageFormatter',
        'yii\i18n\MessageSource',
        'yii\i18n\PhpMessageSource',
        'yii\jui\Accordion',
        'yii\jui\AutoComplete',
        'yii\jui\DatePicker',
        'yii\jui\DatePickerLanguageAsset',
        'yii\jui\Dialog',
        'yii\jui\Draggable',
        'yii\jui\Droppable',
        'yii\jui\InputWidget',
        'yii\jui\JuiAsset',
        'yii\jui\Menu',
        'yii\jui\ProgressBar',
        'yii\jui\Resizable',
        'yii\jui\Selectable',
        'yii\jui\Slider',
        'yii\jui\SliderInput',
        'yii\jui\Sortable',
        'yii\jui\Spinner',
        'yii\jui\Tabs',
        'yii\log\DbTarget',
        'yii\log\EmailTarget',
        'yii\log\FileTarget',
        'yii\log\SyslogTarget',
        'yii\mail\MailEvent',
        'yii\rbac\Assignment',
        'yii\rbac\Item',
        'yii\rbac\Permission',
        'yii\rbac\Role',
        'yii\redis\Cache',
        'yii\redis\Connection',
        'yii\redis\LuaScriptBuilder',
        'yii\redis\Session',
        'yii\rest\Serializer',
        'yii\rest\UrlRule',
        'yii\test\ActiveFixture',
        'yii\test\ArrayFixture',
        'yii\test\InitDbFixture',
        'yii\validators\BooleanValidator',
        'yii\validators\CompareValidator',
        'yii\validators\DateValidator',
        'yii\validators\DefaultValueValidator',
        'yii\validators\EachValidator',
        'yii\validators\EmailValidator',
        'yii\validators\ExistValidator',
        'yii\validators\FileValidator',
        'yii\validators\FilterValidator',
        'yii\validators\ImageValidator',
        'yii\validators\InlineValidator',
        'yii\validators\IpValidator',
        'yii\validators\NumberValidator',
        'yii\validators\RangeValidator',
        'yii\validators\RegularExpressionValidator',
        'yii\validators\RequiredValidator',
        'yii\validators\SafeValidator',
        'yii\validators\StringValidator',
        'yii\validators\UniqueValidator',
        'yii\validators\UrlValidator',
        'yii\validators\ValidationAsset',
        'yii\web\AssetConverter',
        'yii\web\Cookie',
        'yii\web\GroupUrlRule',
        'yii\web\HeaderCollection',
        'yii\web\HtmlResponseFormatter',
        'yii\web\JqueryAsset',
        'yii\web\JsonParser',
        'yii\web\JsonResponseFormatter',
        'yii\web\Link',
        'yii\web\MultipartFormDataParser',
        'yii\web\UrlManager',
        'yii\web\UrlNormalizer',
        'yii\web\UrlRule',
        'yii\web\UserEvent',
        'yii\web\XmlResponseFormatter',
        'yii\web\YiiAsset',
        'yii\widgets\ActiveField',
        'yii\widgets\ActiveForm',
        'yii\widgets\ActiveFormAsset',
        'yii\widgets\Block',
        'yii\widgets\Breadcrumbs',
        'yii\widgets\ContentDecorator',
        'yii\widgets\DetailView',
        'yii\widgets\FragmentCache',
        'yii\widgets\InputWidget',
        'yii\widgets\LinkPager',
        'yii\widgets\LinkSorter',
        'yii\widgets\ListView',
        'yii\widgets\MaskedInput',
        'yii\widgets\MaskedInputAsset',
        'yii\widgets\Menu',
        'yii\widgets\Pjax',
        'yii\widgets\PjaxAsset',
        'yii\widgets\Spaceless',
    ];

    /**
     * @var array 持久化的类实例
     */
    public static $persistInstances = [];

    /**
     * 在最终构造类时, 尝试检查类的别名
     *
     * @inheritdoc
     */
    protected function build($class, $params, $config)
    {
        // 检查类的别名
        if (isset(self::$classAlias[$class]))
        {
            $class = self::$classAlias[$class];
            //echo "alias: $class\n";
        }

        // 构造方法参数为空才走这个流程
        if ($class && in_array($class, self::$persistClasses))
        {
            /* @var $reflection ReflectionClass */
            list ($reflection, $dependencies) = $this->getDependencies($class);
            if ( ! isset(self::$persistInstances[$class]))
            {
                self::$persistInstances[$class] = $reflection->newInstanceWithoutConstructor();
            }
            $object = clone self::$persistInstances[$class];
            // 如果有params参数的话, 则交给构造方法去执行
            // 这里的逻辑貌似太简单了..
            if ($params)
            {
                $reflection->getConstructor()->invokeArgs($object, $params);
            }
            // 执行一些配置信息
            Yii::configure($object, $config);
            if ($object instanceof Object)
            {
                $object->init();
            }
            return $object;
        }
        //echo "build: $class - ".json_encode($params)."\n";

        return parent::build($class, $params, $config);
    }
}
