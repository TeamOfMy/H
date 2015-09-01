<?php

namespace Core;

use Closure;
use Core\Config\Repository;
use Exception;
use ErrorException;
use FastRoute\Dispatcher;
// 调用了 laravel 依赖注入的容器机制
use Illuminate\Container\Container;

// 使用symfony的请求和响应类
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Application extends Container
{
    /**
     * 请求对象
     * @var Request
     */
    protected $request;
    protected $router;

    protected $dispatcher;

    /**
     * All of the routes waiting to be registered.
     *
     * @var array
     */
    protected $routes = [];
    /**
     * 已执行过的 服务绑定方法
     *
     * @var array
     */
    protected $ranServiceBinders = [];

    protected $configPath;

    protected $basePath;

    /**
     * 记录那些 加载过 的配置选项
     *
     * @var array
     */
    protected $loadedConfigurations = [];

    public function __construct()
    {
//        $this->request = $request;
        // TODO 分配时间区
//        date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

//        $this->basePath = $basePath;
        $this->bootstrapContainer();
        $this->registerErrorHandling();
    }

    /**
     * 框架中的核心容器
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        // 初始化本类
        static::setInstance($this);

        // 把本应用绑定到系统中也作为一种服务
        $this->instance('app', $this);

        // 注册容器中的服务别名
        $this->registerContainerAliases();
    }

    public function run()
    {
        $domain = explode('.', $_SERVER['HTTP_HOST']);
        $len = count($domain);
        $_W['v_remember_encrypt'] = '.' . $domain[$len - 2] . '.' . $domain[$len - 1];//将domain注册到配置文件
        ini_set('session.cookie_domain', $_W['v_remember_encrypt']);//限制二级域名;
        session_start();

        $this->init();
        $match = $this->router->match($this->request->getPathInfo());
        if ($match != false) {
            $this->request->params = array_merge($this->request->params, $match['params']);
        } else {
            throw new \Exception('请求的地址有问题');
        }

        $target = $match['target'];
        $return = array();

        if (empty($charset)) {
            $charset = 'utf-8';
        }
        if (empty($contentType)) {
            $contentType = 'text/html';
        }
        // 网页字符编码
        header('Content-Type:' . $contentType . '; charset=' . $charset);
        header('Cache-control: ');  // 页面缓存控制
        header('X-Powered-By:Video');
        ob_start();
        if (is_callable($target)) {
            $return = call_user_func($target, $match['params']);
        }

        if (is_string($target)) {
            list($controller, $method) = explode(':', $target);
            $controller = new $controller($this->request);
            call_user_func_array(array($controller, $method), $match['params']);
        }
        ob_end_flush();
        // 输出模板文件
    }

    /**
     * 注册核心的错误异常处理
     *
     * @return void
     */
    protected function registerErrorHandling()
    {
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            if (!is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
                // TODO 构建处理错误的handler
//                $this->handleUncaughtException($handler);
            }
        });
    }

    /**
     * 从容器中解析给定的类型
     *
     * @param  string $abstract
     * @param  array $parameters
     * @return mixed
     */
    public function make($abstract, $parameters = [])
    {
        if (array_key_exists($abstract, $this->availableBindings) &&
            !array_key_exists($this->availableBindings[$abstract], $this->ranServiceBinders)
        ) {
            $this->{$method = $this->availableBindings[$abstract]}();

            $this->ranServiceBinders[$method] = true;
        }
        return parent::make($abstract, $parameters);
    }


    /**
     * 运行应用程序和发送响应 系统的核心部分
     *
     * @param  SymfonyRequest|null $request
     * @return void
     */
    public function goRun($request = null)
    {
        // TODO 处理request的扩展性 支持其他的request
        $request = $this->make('request');

        $response = $this->dispatch($request);
        if ($response instanceof SymfonyResponse) {
            $response->send();
        } else {
            echo (string)$response;
        }
    }


    /**
     * 分发请求
     *
     * @param  SymfonyRequest|null $request
     * @return Response
     */
    public function dispatch($request = null)
    {
        if ($request) {
            $this->instance('Illuminate\Http\Request', $request);
            $this->ranServiceBinders['registerRequestBindings'] = true;

            $method = $request->getMethod();
            $pathInfo = $request->getPathInfo();
        } else {
            $method = $this->getMethod();
            $pathInfo = $this->getPathInfo();
        }

        var_dump($method);
        var_dump($pathInfo);
        try {
            // 非传参数类的路由直接调用 提高效率  如 /task
            if (isset($this->routes[$method . $pathInfo])) {
                return $this->handleFoundRoute([true, $this->routes[$method . $pathInfo]['action'], []]);
            } else {
                // 根据匹配去调用路由  /task/end/(123)
                return $this->handleDispatcherResponse(
                // 调用 fastRoute 类 进行匹配
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            }

        } catch (Exception $e) {
            var_dump($e);
            return $this->sendExceptionToHandler($e);
        }
    }

    /**
     * 为应用程序创建FastRoute调度实例
     *
     * 使用的是fastRoute的路由模块来处理的 addRoute是fastRoute中的方法
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return $this->dispatcher ?: \FastRoute\simpleDispatcher(function ($r) {
            // 循环注册路由到系统中
            foreach ($this->routes as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        });
    }

    /**
     * 向处理程序发送异常并返回响应。
     *
     * @param  Exception $e
     * @return Response
     */
    protected function sendExceptionToHandler($e)
    {
        $handler = $this->make('Illuminate\Contracts\Debug\ExceptionHandler');

        $handler->report($e);

        return $handler->render($this->make('request'), $e);
    }


    /**
     * 从FastRoute 分发结果类型来处理响应
     *
     * @param  array $routeInfo
     * @throws
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // TODO notFoundException 构建
                throw new Exception();

            case Dispatcher::METHOD_NOT_ALLOWED:
                // TODO　需要构建方法不允许的的错误提示
                throw new Exception($routeInfo[1]);

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }


    /**
     * 根据路由去触发程序
     *
     * @param  array $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        $this->currentRoute = $routeInfo;

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

    /**
     * 调用基于数组的路由的闭包。
     *
     * @param  array $routeInfo
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];

        // 如果是直接使用 controller的方法  调用controller的方法
        if (isset($action['uses'])) {
            return $this->prepareResponse($this->callControllerAction($routeInfo));
        }

        // TODO　如果不是controller的方式的话 function() 回调函数的处理
        foreach ($action as $value) {
            // 如果是回调函数 直接就执行了 TODO
            if ($value instanceof Closure) {
                $closure = $value->bindTo(new \Core\Closure);
                break;
            }
        }
        try {
            return $this->prepareResponse($this->call($closure, $routeInfo[2]));
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * 呼叫控制器为基础的路由。
     *
     * @param  array $routeInfo
     * @throws
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        list($controller, $method) = explode('@', $routeInfo[1]['uses']);
var_dump($controller);
        if (!method_exists($instance = $this->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        return $this->callControllerCallable(
            [$instance, $method], $routeInfo[2]
        );

    }

    /**
     * 根据给定的参数调用对应的controller
     *
     * @param  array $callable
     * @param  array $parameters
     * @return mixed
     */
    protected function callControllerCallable(array $callable, array $parameters)
    {
        try {
            return $this->prepareResponse(
                $this->call($callable, $parameters)
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }


    /**
     * 准备发送的响应。
     *
     * 这儿可以扩展成其他的很多响应类型
     *
     * @param  mixed $response
     * @return Response
     */
    public function prepareResponse($response)
    {
        if (!$response instanceof SymfonyResponse) {
            $response = new Response($response);
        }
        return $response;
    }

    /**
     * 注册GET 方式请求的路由到系统
     *
     * @param  string $uri
     * @param  mixed $action
     * @return $this
     */
    public function get($uri, $action)
    {
        $this->addRoute('GET', $uri, $action);

        return $this;
    }

    /**
     * 添加注册进来的路由到收集器中
     * $this->routes 就是收集器
     *
     * @param  string $method
     * @param  string $uri
     * @param  mixed $action
     */
    public function addRoute($method, $uri, $action)
    {
        $action = $this->parseAction($action);

        if (isset($this->groupAttributes)) {
            if (isset($this->groupAttributes['prefix'])) {
                $uri = trim($this->groupAttributes['prefix'], '/') . '/' . trim($uri, '/');
            }

            // 合并到路由域中 可以给路由分组的
            $action = $this->mergeGroupAttributes($action);
        }

        $uri = '/' . trim($uri, '/');

        if (isset($action['as'])) {
            $this->namedRoutes[$action['as']] = $uri;
        }

        $this->routes[$method . $uri] = ['method' => $method, 'uri' => $uri, 'action' => $action];
    }

    /**
     * 匹配方法到一个数组中
     *
     * @param  mixed $action
     * @return array
     */
    protected function parseAction($action)
    {
        if (is_string($action)) {
            return ['uses' => $action];
        } elseif (!is_array($action)) {
            return [$action];
        }

        return $action;
    }

    /**
     * 获取 http 请求的方法
     *
     * @return string
     */
    protected function getMethod()
    {
        if (isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        } else {
            return $_SERVER['REQUEST_METHOD'];
        }
    }

    /**
     * 获取请求的pathinfo
     *
     * @return string
     */
    public function getPathInfo()
    {
        $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        return '/' . trim(str_replace('?' . $query, '', $_SERVER['REQUEST_URI']), '/');
    }

    /**
     * 处理未捕获的异常器
     *
     * TODO 这里可以重写handler
     *
     * @param  Exception $e
     * @return void
     */
    protected function handleUncaughtException($e)
    {
        $handler = $this->make('Illuminate\Contracts\Debug\ExceptionHandler');

        $handler->report($e);
        $handler->render($this->make('request'), $e)->send();
    }

    /**
     * 确定错误类型，是否致命
     *
     * @param  int $type
     * @return bool
     */
    protected function isFatalError($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }


    /**
     * 注册错误处理器
     *
     * @return void
     */
    protected function registerErrorBindings()
    {
        if (!$this->bound('Illuminate\Contracts\Debug\ExceptionHandler')) {
            $this->singleton(
                'Illuminate\Contracts\Debug\ExceptionHandler', 'Core\Exceptions\Handler'
            );
        }
    }

    /**
     * 注册请求类到容器中
     *
     * @return void
     */
    protected function registerRequestBindings()
    {
        $this->singleton('request', function () {
            return SymfonyRequest::createFromGlobals();
        });
    }

    /**
     * 配置和加载给定的组件和提供程序。
     *
     * @param  string $config
     * @param  array|string $providers
     * @param  string|null $return
     * @return mixed
     */
    protected function loadComponent($config, $providers, $return = null)
    {
        $this->configure($config);

        foreach ((array)$providers as $provider) {
            $this->register($provider);
        }

        return $this->make($return ?: $config);
    }

    /**
     * 加载配置文件到系统中
     *
     * @param string $name 配的选项key名  如：database
     * @return void
     */
    public function configure($name)
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;
        $path = $this->getConfigurationPath($name);
        var_dump($path);
        if ($path) {
            $this->make('config')->set($name, require $path);
        }
    }

    /**
     * 注册配置加载到容器中
     *
     * @return void
     */
    protected function registerConfigBindings()
    {
        $this->singleton('config', function () {
            return new Repository();
        });
    }

    /**
     * 根据给出的 配置选项的值获取对应的加载文件的路径
     *
     * @param  string $name
     * @return string
     */
    protected function getConfigurationPath($name)
    {
        $appConfigPath = ($this->configPath ?: $this->basePath('config')) . '/' . $name . '.php';
        if (file_exists($appConfigPath)) {
            return $appConfigPath;
        } elseif (file_exists($path = __DIR__ . '/../App/Config/' . $name . '.php')) {
            return $path;
        }
    }

    /**
     * 获取系统的准确路径
     * 用于加载配置文件之类的
     *
     * @param  string $path
     * @return string
     */
    public function basePath($path = null)
    {
        if (isset($this->basePath)) {
            return $this->basePath . ($path ? '/' . $path : $path);
        }

        // TODO 命令行模式可能会有问题 需要重写处理

        $this->basePath = realpath(getcwd() . '/../');


        return $this->basePath($path);
    }

    /**
     * 注册模板引擎
     *
     * @return void
     */
    protected function registerViewBindings()
    {
        $this->configure('template');
        $this->singleton('view', function () {
            $loader = new \Twig_Loader_Filesystem ($this->make('config')['template.template_dir']);
            return new \Twig_Environment ($loader, array (
                'cache' => $this->make('config')['cache_dir'],
                'debug' => $this->make('config')['debug'],
                'auto_reload' => $this->make('config')['auto_reload'],
            ) );

        });
    }


    /**
     * 注册 DB 到容器中
     *
     * @return void
     */
    protected function registerDatabaseBindings()
    {
        $this->configure('database');
        $this->singleton('db', function () {
            $capsule =  new \Illuminate\Database\Capsule\Manager($this);
            $capsule->addConnection($this->make('config')['database']);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        });
    }

    /**
     * 注册核心容器中的别名
     *
     * @return void
     */
    protected function registerContainerAliases()
    {
        $this->aliases = [
            'Core\Config\Repository' => 'config',
            'Illuminate\Container\Container' => 'app',
//            'Illuminate\Contracts\Redis\Database' => 'redis',
        ];
    }


    /**
     * 可用容器绑定及其各自的加载方法
     *
     * @var array
     */
    public $availableBindings = [
        'config' => 'registerConfigBindings',
        'db' => 'registerDatabaseBindings', // 注册 DB 类
        'Illuminate\Contracts\Debug\ExceptionHandler' => 'registerErrorBindings',// 错误处理类 需要重写
//        'Illuminate\Contracts\Hashing\Hasher' => 'registerHashBindings',
        'log' => 'registerLogBindings',
        'Psr\Log\LoggerInterface' => 'registerLogBindings',
//        'redis' => 'registerRedisBindings',
        'request' => 'registerRequestBindings', // 生成请求类
//        'validator' => 'registerValidatorBindings',
        'view' => 'registerViewBindings',
    ];
}