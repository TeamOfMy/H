<?php

namespace Core;


class Container
{
    protected $s = array();

    public function __set($k, $c)
    {
        $this->s[$k] = $c;
    }

    public function __get($k)
    {
        // return $this->s[$k]($this);
        return $this->build($this->s[$k]);
    }

    /**
     * �Զ��󶨣�Autowiring���Զ�������Automatic Resolution��
     *
     * @param string $className
     * @return object
     * @throws Exception
     */
    public function build($className)
    {
        var_dump($className);
        // ���������������Anonymous functions����Ҳ�бհ�������closures��
        if ($className instanceof \Closure) {
            // ִ�бհ��������������
            return $className($this);
        }

        /** @var ReflectionClass $reflector */
        $reflector = new \ReflectionClass($className);

        // ������Ƿ��ʵ����, �ų�������abstract�Ͷ���ӿ�interface
        if (!$reflector->isInstantiable()) {
            throw new Exception("Can't instantiate this.");
        }

        /** @var ReflectionMethod $constructor ��ȡ��Ĺ��캯�� */
        $constructor = $reflector->getConstructor();

        // ���޹��캯����ֱ��ʵ����������
        if (is_null($constructor)) {
            return new $className;
        }

        // ȡ���캯������,ͨ�� ReflectionParameter ���鷵�ز����б�
        $parameters = $constructor->getParameters();

        // �ݹ�������캯���Ĳ���
        $dependencies = $this->getDependencies($parameters);

        // ����һ�������ʵ���������Ĳ��������ݵ���Ĺ��캯����
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters)
    {
        $dependencies = [];

        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            /** @var ReflectionClass $dependency */
            $dependency = $parameter->getClass();

            if (is_null($dependency)) {
                // �Ǳ���,��Ĭ��ֵ������Ĭ��ֵ
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                // ��һ���࣬�ݹ����
                $dependencies[] = $this->build($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws Exception
     */
    public function resolveNonClass($parameter)
    {
        // ��Ĭ��ֵ�򷵻�Ĭ��ֵ
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception('I have no idea what to do here.');
    }
}