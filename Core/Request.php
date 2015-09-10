<?php

namespace Core;
use Illuminate\Support\Arr;
use \Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * 扩展请求对象 用于兼容有些组件的方法
 *  如:
 *      分页pagination
 *
 * Class Request
 * @package Core
 */
class Request extends SymfonyRequest
{
    /**
     * 获取对应的 请求或者请求对象
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected function getInputSource()
    {
        return $this->getMethod() == 'GET' ? $this->query : $this->request;
    }

    /**
     * 获取提交过来的值中的数据
     *
     * @param null $key 要获取对应的提交的值
     * @param null $default 默认值 获取不到就用这个默认的
     * @return mixed
     */
    public function input($key=null,$default=null)
    {
        $input = $this->getInputSource()->all() + $this->query->all();
        return Arr::get($input,$key,$default);
    }

    /**
     * 扩展了SymfonyRequest 的方法 用于获取url地址
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/','',$this->getUri()),'/');
    }

}