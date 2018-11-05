<?php

namespace think;

use think\exception\ValidateException;
use traits\controller\Jump;

Loader::import('controller/Jump', TRAIT_PATH, EXT);

class WeappController
{
    use Jump;

    /**
     * @var \think\View 视图类实例
     */
    protected $view;

    /**
     * @var \think\Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * @var array 当前子插件根目录
     */
    protected $weapp_path          =   '';

    /**
     * @var array 当前子插件配置文件路径
     */
    protected $config_file         =   '';

    /**
     * @var array 当前子插件模块分组
     */
    protected $weapp_module_name       =   '';

    /**
     * @var array 当前子插件控制器
     */
    protected $weapp_controller_name       =   '';

    /**
     * @var array 当前子插件操作名
     */
    protected $weapp_action_name       =   '';

    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->request = $request;

        $class = get_class($this); // 返回对象的类名
        $wmcArr = explode('\\', $class);
        $this->weapp_module_name = $this->request->param('sm')?:$wmcArr[1]; // 当前插件模块名称
        $this->weapp_controller_name = $this->request->param('sc')?:$wmcArr[3]; // 当前插件控制器名称
        $this->weapp_action_name = $this->request->param('sa')?:'index'; // 当前插件操作名称是

        // 模板路径
        $template = Config::get('template');
        $template['view_path'] = '.'.__ROOT__.'/'.WEAPP_DIR_NAME.'/'.$this->weapp_module_name.'/template/';
        Config::set('template', $template);

        $this->view    = View::instance($template);

        $this->weapp_path   =   WEAPP_DIR_NAME.DS.$this->weapp_module_name.DS;
        if(is_file($this->weapp_path.'config.php')){
            $this->config_file = $this->weapp_path.'config.php';
        }

        // 验证插件的配置完整性
        $this->checkConfig();

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        /*---------*/
        $is_eyou_authortoken = session('web_is_authortoken');
        $is_eyou_authortoken = !empty($is_eyou_authortoken) ? $is_eyou_authortoken : 0;
        $this->assign('is_eyou_authortoken', $is_eyou_authortoken);
        /*--end*/
    }

    public function _empty($name)
    {
        abort(404);
    }

    /**
     * 前置操作
     * @access protected
     * @param  string $method  前置操作方法名
     * @param  array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if ('Weapp' == $this->request->controller() && 'execute' == $this->request->action()) {
            /*插件的前置操作*/
            $sm = $this->weapp_module_name;
            $sc = $this->weapp_controller_name;
            $sa = $this->weapp_action_name;
            if (isset($options['only'])) {
                if (is_string($options['only'])) {
                    $options['only'] = explode(',', $options['only']);
                }

                if (!in_array($sa, $options['only'])) {
                    return;
                }
            } elseif (isset($options['except'])) {
                if (is_string($options['except'])) {
                    $options['except'] = explode(',', $options['except']);
                }

                if (in_array($sa, $options['except'])) {
                    return;
                }
            }

            call_user_func([$this, $method], $sm, $sc, $sa);
            /*--end*/
        } else {
            if (isset($options['only'])) {
                if (is_string($options['only'])) {
                    $options['only'] = explode(',', $options['only']);
                }

                if (!in_array($this->request->action(), $options['only'])) {
                    return;
                }
            } elseif (isset($options['except'])) {
                if (is_string($options['except'])) {
                    $options['except'] = explode(',', $options['except']);
                }

                if (in_array($this->request->action(), $options['except'])) {
                    return;
                }
            }

            call_user_func([$this, $method]);
        }
    }

    /**
     * 检测是否存在模板文件 by 小虎哥
     * @access public
     * @param string $template 模板文件或者模板规则
     * @return bool
     */
    protected function exists($template = '')
    {
        $bool = $this->view->exists($template);
        return $bool;
    }

    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @param  array  $vars     模板输出变量
     * @param  array  $replace  模板替换
     * @param  array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $view_path = Config::get('template.view_path');

        if (empty($template)) {
            $template = $this->weapp_action_name;
        }
        if('' == pathinfo($template, PATHINFO_EXTENSION)){
            $template = str_replace('\\', '/', $template);
            $arr = explode('/', $template);
            if (1 == count($arr)) {
                $template = $view_path.$arr[0];
            } else if (2 == count($arr)) {
                $template = $view_path.$arr[0].DS.$arr[1];
            } else if (3 == count($arr)) {
                $view_path = str_replace('/'.$this->weapp_module_name.'/template/', '/'.$arr[0].'/template/', $view_path);
                $template = $view_path.$arr[1].DS.$arr[2];
            } else {
                $template = $view_path.$arr[count($arr) - 1];
            }
            $template = $template.'.'.Config::get('template.view_suffix');
        }
        if (!$this->exists($template)) {
            die("模板文件不存在:$template");
        }
        /*插件模板字符串替换，不能放在构造函数，毕竟构造函数只执行一次 by 小虎哥*/
        $replace['__WEAPP_TEMPLATE__'] = __ROOT__.'/'.WEAPP_DIR_NAME.'/'.$this->weapp_module_name.'/template';
        /*--end*/
        $replace = array_merge(Config::get('view_replace_str'), $replace);
        $config = array_merge(Config::get('template'), $config);
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @param  array  $replace 替换内容
     * @param  array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @param  mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            // 支持场景
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        // 设置错误信息
        if (is_array($message)) {
            $v->message($message);
        }

        // 使用回调验证
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

    /**
     * 验证插件的配置完整性
     * @return string
     * @throws Exception
     */
    final public function checkConfig(){
        $config_check_keys = array('code','name','description','scene','author','version','min_version');
        $config = include $this->config_file;
        foreach ($config_check_keys as $value) {
            if(!array_key_exists($value, $config)) {
                die("插件配置文件config.php不符合官方规范，缺少{$value}数组元素！");
                // throw new \Exception("插件配置文件config.php不符合官方规范，缺少{$value}数组元素！");
            }
        }
        return true;
    }

    /**
     * 获取插件信息
     */
    final public function getWeappInfo($code = ''){
        static $_weapp = array();
        if(empty($code)){
            $config = $this->getConfig();
            $code = !empty($config['code']) ? $config['code'] : $this->weapp_module_name;
        }
        if(isset($_weapp[$code])){
            return $_weapp[$code];
        }
        $values =   array();
        $config  =   M('Weapp')->where('code',$code)->getField('config');
        if(!empty($config)){
            $values   =   json_decode($config, true);
        }
        $_weapp[$code]     =   $values;
        
        return $values;
    }

    /**
     * 获取插件的配置
     */
    final public function getConfig(){
        static $_config = array();
        if(!empty($_config)){
            return $_config;
        }
        $config = include $this->config_file;
        $_config     =   $config;

        return $config;
    }

    /**
     * 插件使用说明
     */
    public function doc(){
        $this->success("该插件开发者未完善使用指南！");
    }
}
