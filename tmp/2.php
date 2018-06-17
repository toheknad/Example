<?php

class foo
{

    public $kol = 0;

    public function mkey($name = '')
    {
        return $name . '12334556666';
    }

    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
        echo $name . ' ' . $value;
    }

    public function setKol($value)
    {
        echo 'kol- ' . $value;

    }

}


$f = new foo();

//echo " first = ${key['last']}";
//echo " first = {$f->mkey('qwer')} ";

//echo method_exists($f,'m_Key');
echo class_exists('Foo');

