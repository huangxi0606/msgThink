<?php
/**
 * Created by PhpStorm.
 * User: Hhx
 * Date: 2018/9/7
 * Time: 15:00
 */

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Common extends Controller
{
    public function __construct(){
        parent::__construct();
        if(!Session::has('admin.name')){
            $this->redirect('/admin/login/');
        }
        $request= $request= \think\Request::instance();
        $module_name=$request->module();
        $controller_name=$request->controller();
        $active_url=$module_name.'/'.$controller_name;
//        var_dump($active_url);
//        var_dump(session('privilege'));
//        var_dump(in_array($active_url,session('privilege')));exit;
        if(session('privilege')!='*' && !in_array($active_url,session('privilege'))){
            $this->error('没有权限访问该功能！');
        }

    }
}