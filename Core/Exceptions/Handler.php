<?php
namespace Core\Exceptions;
use Exception;
class Handler
{
    public function report(Exception $e)
    {
        var_dump($e->getMessage());
    }

    public function render($request, Exception $e)
    {
        var_dump($e->getMessage());
    }
}