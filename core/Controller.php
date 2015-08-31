<?php

namespace Core;

use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Response;

class Controller
{
    protected $twig;
    protected $request;
    protected $response;
    public function __construct(Container $container)
    {
        $this->container = $container;
//        $this->request = $request;
    }


    /**
     * äÖÈ¾Ä£°å
     *
     * @param $tpl
     * @param $params
     * @return Response
     */
    public function render($tpl,$params)
    {
        $twig = $this->container->make('view');
        return new Response($twig->render($tpl,$params));
//        $response = new Response();
//        $response->display($tpl,$params);
    }

}
