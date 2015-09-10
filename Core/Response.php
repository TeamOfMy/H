<?php

namespace Core;

use \Twig_Loader_Filesystem;
class Response
{
    protected $request;
    protected $return;

    public function __construct()
    {

    }

    public function send()
    {
//        $content = $this->return;
//        if (is_array($content)) {
//            $content = json_encode($content,JSON_UNESCAPED_UNICODE);
//        }

        $content = ob_end_clean();
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
        // 输出模板文件
        echo $content;
    }

    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     * 模板的解析
     *
     *
     * @param $tplname
     * @param $params
     */
    public function display($tplname, $params)
    {
        // 创建twig 的模板加载路径对象
        $path = BASEDIR . '/App/View/';
        $loader = new \Twig_Loader_Filesystem($path);
        $env = $this->makeEnvironment($loader);
        $env->display($tplname . '.html.twig', $params);
    }

    /**
     * 创建模板环境对象
     * @param \Twig_Loader_Filesystem $loader
     * @return \Twig_Environment
     */
    protected function makeEnvironment(\Twig_Loader_Filesystem $loader)
    {        //配置模板环境选项
        $isDebug = true;
        $ops = array('debug' => $isDebug);
        if ($isDebug) {
            $ops['strict_variables'] = true;
        }
        $twig = new \Twig_Environment($loader, $ops);
        //添加常量
        $this->addGlobal($twig);
        //添加模板方法
        $this->addFunction($twig);
        return $twig;
    }

    /**
     * 新增模板所需要的一些常量
     * @param \Twig_Environment $twig
     */
    protected function addGlobal(\Twig_Environment $twig)
    {
        //添加常用的全局变量
        $twig->addGlobal('__CSS__', BASEDIR . '/App/Public/css');
        $twig->addGlobal('__JS__', BASEDIR . '/App/Public/js');
        $twig->addGlobal('__IMAGE__', BASEDIR . '/App/Public/image');

        $twig->addGlobal('STATIC_PATH', '/public');
        $twig->addGlobal('IMG_PATH', '/public/images');
    }

    /**
     * 增加模板函数
     * @param \Twig_Environment $twig
     */
    protected function addFunction(\Twig_Environment $twig)
    {
        //url处理
        $url = new \Twig_SimpleFunction('U', array('Core\Router', 'generate'));
        $twig->addFunction($url);
    }
}