<?php

class Hoge
{
    public function callbackMethod()
    {
        $args = func_get_args();
        if (count($args) > 0) {
            var_dump($args);
            return;
        }

        echo "hoge";
    }

    public static function staticCallbackMethod()
    {
        $args = func_get_args();
        if (count($args) > 0) {
            var_dump($args);
            return;
        }

        echo "fuga";
    }

    public function test1()
    {
        $a = __CLASS__;
        //$a;
        //$a;
    }

    public function test2()
    {
        $a = get_called_class();
//        __CLASS__;
//        __CLASS__;
//        __CLASS__;
    }
}
