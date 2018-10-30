<?php

namespace app\admin\behavior;

/**
 * 管理员权限控制
 */
load_trait('controller/Jump');
class AuthRoleBehavior
{
    use \traits\controller\Jump;
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;
    protected static $admin_info;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {
        !isset(self::$moduleName) && self::$moduleName = request()->module();
        !isset(self::$controllerName) && self::$controllerName = request()->controller();
        !isset(self::$actionName) && self::$actionName = request()->action();
        !isset(self::$method) && self::$method = strtoupper(request()->method());
        !isset(self::$admin_info) && self::$admin_info = session('admin_info');
    }

    /**
     * 模块初始化
     * @param array $params 传入参数
     * @access public
     */
    public function moduleInit(&$params)
    {
        
    }

    /**
     * 操作开始执行
     * @param array $params 传入参数
     * @access public
     */
    public function actionBegin(&$params)
    {
        if (-1 != self::$admin_info['role_id']) {
            // 检测全局的增、改、删的权限
            $this->cud_access();
            // 检测栏目管理的每个栏目权限
            $this->arctype_access();
            // 检测内容管理每个栏目对应的内容里列表等权限
            $this->archives_access();
        }
    }

    /**
     * 视图内容过滤
     * @param array $params 传入参数
     * @access public
     */
    public function viewFilter(&$params)
    {

    }

    /**
     * 应用结束
     * @param array $params 传入参数
     * @access public
     */
    public function appEnd(&$params)
    {

    }

    /**
     * 检测全局的增、改、删的权限
     * @access private
     */
    private function cud_access()
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $act = strtolower(self::$actionName);
        $actArr = ['add','edit','del'];
        foreach ($actArr as $key => $cud) {
            $act = preg_replace('/^(.*)_('.$cud.')$/i', '$2', $act); // 同名add 或者以_add类似结尾都符合
            if ($act == $cud) {
                $admin_info = self::$admin_info;
                $auth_role_info = !empty($admin_info['auth_role_info']) ? $admin_info['auth_role_info'] : [];
                $cudArr = !empty($auth_role_info['cud']) ? $auth_role_info['cud'] : [];
                if (!in_array($act, $cudArr)) {
                    $this->error('您没有操作权限，请联系超级管理员分配权限');
                }
                break;
            }
        }
        /*--end*/
    }

    /**
     * 检测栏目管理的每个栏目权限
     * @access private
     */
    private function arctype_access()
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $ctl_all = strtolower(self::$controllerName.'@*');
        $ctlArr = ['arctype@*'];
        if (in_array($ctl_all, $ctlArr)) {
            $typeids = [];
            if (in_array(strtolower(self::$actionName), ['edit','del'])) {
                $typeids[] = I('id/d', 0);
            } else if (in_array(strtolower(self::$actionName), ['add'])) {
                $typeids[] = I('parent_id/d', 0);
            }
            if (!$this->is_check_arctype($typeids)) {
                $this->error('您没有操作权限，请联系超级管理员分配权限');
            }
        }
        /*--end*/
    }

    /**
     * 检测内容管理每个栏目对应的内容里列表等权限
     * @access private
     */
    private function archives_access()
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $ctl = strtolower(self::$controllerName);
        $act = strtolower(self::$actionName);
        $ctl_act = $ctl.'@'.$act;
        $ctl_all = $ctl.'@*';
        $ctlArr = ['arctype@single','archives@*','article@*','product@*','images@*','download@*','guestbook@*'];
        if (in_array($ctl_act, $ctlArr) || in_array($ctl_all, $ctlArr)) {
            $typeids = [];
            if (in_array($act, ['add','edit','del'])) {
                $aids = [];
                switch ($act) {
                    case 'edit':
                        $aids = I('id/a', []);
                        break;

                    case 'del':
                        $aids = I('del_id/a', []);
                        break;
                    
                    default:
                        # code...
                        break;
                }
                if (!empty($aids)) {
                    $typeids = M('archives')->where('aid','IN',$aids)->column('typeid');
                }
            } else {
                $typeids[] = I('typeid/d', 0);
            }
            if (!$this->is_check_arctype($typeids)) {
                $this->error('您没有操作权限，请联系超级管理员分配权限');
            }
        }
        /*--end*/
    }

    /**
     * 检测栏目是否有权限
     */
    private function is_check_arctype($typeids = []) {  
        $bool_flag = true;
        $admin_info = self::$admin_info;
        if (-1 != $admin_info['role_id']) {
            $auth_role_info = $admin_info['auth_role_info'];
            $permission = $auth_role_info['permission'];

            foreach ($typeids as $key => $tid) {
                if (0 < intval($tid) && !in_array($tid, $permission['arctype'])) {
                    $bool_flag = false;
                    break;
                }
            }
        }

        return $bool_flag;
    }
}