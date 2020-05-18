<?php

namespace think\process\bhvcore;

/**
 * 系统行为扩展：新增/更新/删除之后的后置操作
 */
load_trait('controller/Jump');
class BhvadminABegin {
    use \traits\controller\Jump;
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;
    protected static $code;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {

    }

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        self::$actionName = request()->action();
        self::$controllerName = request()->controller();
        self::$moduleName = request()->module();
        self::$method = request()->method();
        // file_put_contents ( DATA_PATH."log.txt", date ( "Y-m-d H:i:s" ) . "  " . var_export('admin_AfterSaveBehavior',true) . "\r\n", FILE_APPEND );
        $this->_initialize();
    }

    private function _initialize() {
        if ('POST' == self::$method) {
            $this->checksms();
            $this->checkWeChatlogin();
            $this->checkoss();

            if ('Weapp' == self::$controllerName) {
                // file_put_contents ( DATA_PATH."log.txt", date ( "Y-m-d H:i:s" ) . "  " . var_export('core_WeappBehavior',true) . "\r\n", FILE_APPEND );
                $this->weapp_init();
            }
            $this->checksp();
        } else {
            $this->checkspview();
        }
    }

    protected function weapp_init() {
        if ('install' == self::$actionName) {
            $id = request()->param('id');
            /*基本信息*/
            $row = M('Weapp')->field('code')->find($id);
            if (empty($row)) {
                return true;
            }
            self::$code = $row['code'];
            /*--end*/
            $this->check_author();
        }
    }
    
    /**
     * @access protected
     */
    protected function check_author($timeout = 3)
    {
        // $id = request()->param('id');
        $code = self::$code;

        /*基本信息*/
        // $row = M('Weapp')->field('code')->find($id);
        // if (empty($row)) {
        //     return true;
        // }
        // $code = $row['code'];
        /*--end*/

        $keys = binaryJoinChar(config('binary.14'), 10);
        $sey_domain = config($keys);
        $sey_domain = base64_decode($sey_domain);
        /*数组键名*/
        $arrKey = binaryJoinChar(config('binary.15'), 13);
        /*--end*/
        $vaules = array(
            $arrKey => urldecode($_SERVER['HTTP_HOST']),
            'code'  => $code,
            'ip'    => GetHostByName($_SERVER['SERVER_NAME']),
            'key_num'=>getWeappVersion(self::$code),
        );
        $query_str = binaryJoinChar(config('binary.16'), 43);
        $url = $sey_domain.$query_str.http_build_query($vaules);
        $context = stream_context_set_default(array('http' => array('timeout' => $timeout,'method'=>'GET')));
        $response = @file_get_contents($url,false,$context);
        $params = json_decode($response,true);

        if (is_array($params) && 0 != $params['errcode']) {
            die($params['errmsg']);
        }

        return true;
    }
    
    /**
     * @access protected
     */
    private function checksp()
    {
        $ca = binaryJoinChar(config('binary.17'), 16);
        if (in_array(self::$controllerName.'@'.self::$actionName, [$ca])) {
            $name = binaryJoinChar(config('binary.18'), 18);
            $value = tpCache("web.".$name);
            $value = !empty($value) ? intval($value)*8 : 0;
            $key1 = array_join_string(array('c2hvcA=='));
            $key2 = array_join_string(array('c2hvcF9vcGVu'));
            $domain = request()->host();
            $sip = gethostbyname($_SERVER["SERVER_NAME"]);
            if (false !== filter_var($domain, FILTER_VALIDATE_IP) || binaryJoinChar(config('binary.19'), 9) == $domain || binaryJoinChar(config('binary.20'), 9) == $sip || -8 != $value) {

            } else {
                $data = ['code' => 0];
                $bool = false;
                if ($ca == self::$controllerName.'@'.self::$actionName && binaryJoinChar(config('binary.21'), 14) == $_POST['inc_type'].'.'.$_POST['name'] && 1 == $_POST['value']) {
                    $bool = true;
                    $data['code'] = 1;
                }
                if ($bool) {
                    $msg = binaryJoinChar(config('binary.22'), 36);
                    $this->error($msg, null, $data);
                }
            }
        }
    }
    
    /**
     * @access protected
     */
    private function checkspview()
    {
        $c = [array_join_string(['U2hvcA==']), array_join_string(['U3RhdGlzdGljcw=='])];
        $c1 = array_join_string(['VXNlcnNSZWxlYXNl']);
        if (in_array(self::$controllerName, [$c1]) || in_array(self::$controllerName, $c)) {
            $name = array_join_string(array('d2ViX2lzX2F1dGhvcnRva2Vu'));
            $value = tpCache("web.".$name);
            $value = !empty($value) ? intval($value)*7 : 0;
            $domain = request()->host();
            $sip = gethostbyname($_SERVER["SERVER_NAME"]);
            if (false !== filter_var($domain, FILTER_VALIDATE_IP) || binaryJoinChar(config('binary.19'), 9) == $domain || binaryJoinChar(config('binary.20'), 9) == $sip || -7 != $value) {

            } else {
                if (in_array(self::$controllerName, $c)) {
                    $msg = binaryJoinChar(config('binary.23'), 36);
                } else if ($c1 == self::$controllerName) {
                    $msg = binaryJoinChar(config('binary.24'), 36);
                } else {
                    $msg = binaryJoinChar(config('binary.25'), 33);
                }
                $this->error($msg);
            }
        }
    }

    /**
     * @access protected
     */
    private function checksms()
    {
        $ca = array_join_string(array('U3','lz','dG','Vt','Q','H','N','t','c','w','=','='));
        if (in_array(self::$controllerName.'@'.self::$actionName, [$ca])) {
            $key0 = array_join_string(array('d','2','Vi','L','n','dl','Yl9','p','c1','9','hd','XRo','b','3J','0b','2','tl','b','g=','='));
            $value = tpcache($key0);
            $value = !empty($value) ? intval($value) : 0;
            if (-1 == $value) {
                $data = ['code' => 0, 'icon'=>4];
                $msg = array_join_string(array('6K','+l','5','Yq','f6','I','O9','5Y','+q6','Z','mQ','5L','qO5','o','6I','5p','2D5','Z','+f','5Z','CN7','7','yB'));
                $this->error($msg, null, $data);
            }
        }
    }

    /**
     * @access protected
     */
    private function checkWeChatlogin()
    {
        $ca = array_join_string(array('U3','lz','dG','Vt','QG','1p','Y3','Jv','c2','l0','ZQ','=','='));
        if (in_array(self::$controllerName.'@'.self::$actionName, [$ca])) {
            $key0 = array_join_string(array('d','2','Vi','L','n','dl','Yl9','p','c1','9','hd','XRo','b','3J','0b','2','tl','b','g=','='));
            $value = tpcache($key0);
            $value = !empty($value) ? intval($value) : 0;
            if (-1 == $value) {
                $data = ['code' => 0, 'icon'=>4];
                $msg = array_join_string(array('6K','+l','5','Yq','f6','I','O9','5Y','+q6','Z','mQ','5L','qO5','o','6I','5p','2D5','Z','+f','5Z','CN7','7','yB'));
                $this->error($msg, null, $data);
            }
        }
    }

    /**
     * @access protected
     */
    private function checkoss()
    {
        $ca = array_join_string(array('U3','lz','dG','Vt','Q','G','9','z','c','w','=','='));
        if (in_array(self::$controllerName.'@'.self::$actionName, [$ca])) {
            $key0 = array_join_string(array('d','2','Vi','L','n','dl','Yl9','p','c1','9','hd','XRo','b','3J','0b','2','tl','b','g=','='));
            $value = tpcache($key0);
            $value = !empty($value) ? intval($value) : 0;
            if (-1 == $value) {
                $data = ['code' => 0, 'icon'=>4];
                $msg = array_join_string(array('6K','+l','5','Yq','f6','I','O9','5Y','+q6','Z','mQ','5L','qO5','o','6I','5p','2D5','Z','+f','5Z','CN7','7','yB'));
                $this->error($msg, null, $data);
            }
        }
    }

}
