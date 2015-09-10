<?php

namespace Core;

use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Response as SymfonyResqonse;

class Controller
{
    protected $twig;
    protected $request;
    protected $response;

    /**
     * 初始化，注入container
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        // 初始化 DB类 TODO 应该用 事件监听的方式来初始化 因为有可能在不需要db的情况下也初始化了
        $container->make('db');

        if(method_exists($this, '__init__')) {
            return call_user_func_array(array($this, '__init__'),array());
        }
    }

    /**
     * 渲染模板
     *
     * @param $tpl
     * @param $params
     * @return Response
     */
    public function render($tpl, $params = [])
    {
        $twig = $this->container->make('view');
        // 必须以html.twig结尾
        return new SymfonyResqonse($twig->render($tpl . '.html.twig', $params, true));
    }

}
