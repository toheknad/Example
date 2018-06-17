<?php

/**
 *  контролер по умолчанию куда попадает весь непонятный мусор
 */

Use Main\Controller;

class DefaultController extends Controller
{

    public function configRouters()
    {
        return [];
    }

    public function Action($params = [])
    {
        return ' Дефолтный контролер! <br>';
    }


}