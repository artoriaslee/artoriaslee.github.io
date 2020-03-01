<?php
/**
 * Project: Catfish_Blog.
 * Author: A.J
 * Date: 2017/12/13
 */
namespace app\ensure\controller;

use think\Db;

class Common
{
    protected function getb($key)
    {
        $re = Db::name('options')->where('option_name','b_'.$key)->field('option_value')->find();
        if(isset($re['option_value']))
        {
            return $re['option_value'];
        }
        else
        {
            return '';
        }
    }
    protected function setb($key,$value)
    {
        $re = Db::name('options')->where('option_name','b_'.$key)->field('option_value')->find();
        if(empty($re))
        {
            $data = [
                'option_name' => 'b_'.$key,
                'option_value' => $value,
                'autoload' => 0
            ];
            Db::name('options')->insert($data);
        }
        else
        {
            Db::name('options')
                ->where('option_name', 'b_'.$key)
                ->update(['option_value' => $value]);
        }
    }
}