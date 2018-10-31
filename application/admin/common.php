<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */
 
/**
 * 检验登陆
 * @param
 * @return bool
 */
function is_adminlogin(){
    $admin_id = session('admin_id');
    if(isset($admin_id) && $admin_id > 0){
        return $admin_id;
    }else{
        return false;
    }
}

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function adminLog($log_info){
    $add['log_time'] = getTime();
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = clientIP();
    $add['log_url'] = request()->baseUrl() ;
    M('admin_log')->add($add);
}

/**
 * 获取管理员登录信息
 */
function getAdminInfo($admin_id = 0)
{
    if ($admin_id > 0) {
        $fields = 'a.*, b.name AS role_name';
        $admin_info = M('admin')
            ->field($fields)
            ->alias('a')
            ->join('__AUTH_ROLE__ b', 'b.id = a.role_id', 'LEFT')
            ->where("a.admin_id", $admin_id)->find();
    } else {
        $admin_info = session('admin_info');
    }
    return $admin_info;
}

/**
 * 获取conf配置文件
 */
function get_conf($name = 'global')
{            
    $arr = include APP_PATH.MODULE_NAME.'/conf/'.$name.'.php';
    return $arr;
}

/**
 * 检测是否有该权限
 */
function is_check_access($str = 'Index@index') {  
    $bool_flag = 1;
    $role_id = session('admin_info.role_id');
    if ($role_id != '-1') {
        $ctl_act = strtolower($str);
        $arr = explode('@', $ctl_act);
        $ctl = !empty($arr[0]) ? $arr[0] : '';
        $act = !empty($arr[1]) ? $arr[1] : '';
        $ctl_all = $ctl.'@*';

        $auth_role_info = session('admin_info.auth_role_info');
        $permission = $auth_role_info['permission'];

        $auth_rule = get_conf('auth_rule');
        $all_auths = []; // 系统全部权限对应的菜单ID
        $admin_auths = []; // 用户当前拥有权限对应的菜单ID
        $diff_auths = []; // 用户没有被授权的权限对应的菜单ID
        foreach($auth_rule as $key => $val){
            $all_auths = array_merge($all_auths, explode(',', strtolower($val['auths'])));
            if (in_array($val['id'], $permission['rules'])) {
                $admin_auths = array_merge($admin_auths, explode(',', strtolower($val['auths'])));
            }
        }
        $all_auths = array_unique($all_auths);
        $admin_auths = array_unique($admin_auths);
        $diff_auths = array_diff($all_auths, $admin_auths);

        if (in_array($ctl_act, $diff_auths) || in_array($ctl_all, $diff_auths)) {
            $bool_flag = false;
        }
    }

    return $bool_flag;
}

/**
 * 根据角色权限过滤菜单
 */
function getMenuList() {
    $menuArr = getAllMenu();
    // return $menuArr;

    $role_id = session('admin_info.role_id');
    if ($role_id != '-1') {
        $auth_role_info = session('admin_info.auth_role_info');
        $permission = $auth_role_info['permission'];

        $auth_rule = get_conf('auth_rule');
        $all_auths = []; // 系统全部权限对应的菜单ID
        $admin_auths = []; // 用户当前拥有权限对应的菜单ID
        $diff_auths = []; // 用户没有被授权的权限对应的菜单ID
        foreach($auth_rule as $key => $val){
            $all_auths = array_merge($all_auths, explode(',', $val['menu_id']), explode(',', $val['menu_id2']));
            if (in_array($val['id'], $permission['rules'])) {
                $admin_auths = array_merge($admin_auths, explode(',', $val['menu_id']), explode(',', $val['menu_id2']));
            }
        }
        $all_auths = array_unique($all_auths);
        $admin_auths = array_unique($admin_auths);
        $diff_auths = array_diff($all_auths, $admin_auths);

        /*过滤三级数组菜单*/
        foreach($menuArr as $k=>$val){
            foreach ($val['child'] as $j=>$v){
                foreach ($v['child'] as $s=>$son){
                    if (in_array($son['id'], $diff_auths)) {
                        unset($menuArr[$k]['child'][$j]['child'][$s]);//过滤菜单
                    }
                }
            }
        }
        /*--end*/

        /*过滤二级数组菜单*/
        foreach ($menuArr as $mk=>$mr){
            foreach ($mr['child'] as $nk=>$nrr){
                if (in_array($nrr['id'], $diff_auths)) {
                    unset($menuArr[$mk]['child'][$nk]);//过滤菜单
                }
            }
        }
        /*--end*/
    }

    return $menuArr;
}

/**
 * 获取左侧菜单
 */
function getAllMenu() {
    $menuArr = false;//extra_cache('admin_all_menu');
    if (!$menuArr) {
        $menuArr = get_conf('menu');
        extra_cache('admin_all_menu', $menuArr);
    }
    return $menuArr;
}

/**
 * 获取全部的模型
 */
if ( ! function_exists('getChanneltypeList'))
{
    function getChanneltypeList()
    {
        $result = extra_cache('admin_channeltype_list_logic');
        if ($result == false)
        {
            $result = model('Channeltype')->getAll('*', array(), 'id');
            extra_cache('admin_channeltype_list_logic', $result);
        }

        return $result;
    }
}

function tpversion($timeout = 3)
{
    if(!empty($_SESSION['isset_push']))
        return false;
    $_SESSION['isset_push'] = 1;
    error_reporting(0);//关闭所有错误报告
    $install_time = DEFAULT_INSTALL_DATE;
    $serial_number = DEFAULT_SERIALNUMBER;

    $constsant_path = APP_PATH.MODULE_NAME.'/conf/constant.php';
    if (file_exists($constsant_path)) {
        require_once($constsant_path);
        defined('INSTALL_DATE') && $install_time = INSTALL_DATE;
        defined('SERIALNUMBER') && $serial_number = SERIALNUMBER;
    }
    $curent_version = getCmsVersion();
    $mysqlinfo = \think\Db::query("SELECT VERSION() as version");
    $mysql_version  = $mysqlinfo[0]['version'];
    $vaules = array(            
        'domain'=>$_SERVER['HTTP_HOST'], 
        'last_domain'=>$_SERVER['HTTP_HOST'], 
        'key_num'=>$curent_version, 
        'install_time'=>$install_time, 
        'serial_number'=>$serial_number,
        'ip'    => GetHostByName($_SERVER['SERVER_NAME']),
        'phpv'  => urlencode(phpversion()),
        'mysql_version' => urlencode($mysql_version),
        'web_server'    => urlencode($_SERVER['SERVER_SOFTWARE']),
    );
    // api_Service_user_push
    $service_ey = config('service_ey');
    $tmp_str = 'L2luZGV4LnBocD9tPWFwaSZjPVNlcnZpY2UmYT11c2VyX3B1c2gm';
    $url = base64_decode($service_ey).base64_decode($tmp_str).http_build_query($vaules);
    stream_context_set_default(array('http' => array('timeout' => $timeout)));
    file_get_contents($url);
}

/**
 * 将新链接推送给百度蜘蛛
 */
function push_zzbaidu($type = 'urls', $aid = '', $typeid = '')
{
    // 获取token的值：http://ziyuan.baidu.com/linksubmit/index?site=http://www.eyoucms.com/
    $aid = intval($aid);
    $typeid = intval($typeid);
    $sitemap_zzbaidutoken = tpCache('sitemap.sitemap_zzbaidutoken');
    if (empty($sitemap_zzbaidutoken) || (empty($aid) && empty($typeid))) {
        return '';
    }

    $urlsArr = array();
    $channeltype_list = model('Channeltype')->getAll('id, ctl_name', array(), 'id');

    if ($aid > 0) {
        $res = M('archives')->field('b.*, a.*, a.aid, b.id as typeid')
            ->alias('a')
            ->join('__ARCTYPE__ b', 'b.id = a.typeid', 'LEFT')
            ->find($aid);
        $ctl_name = $channeltype_list[$res['current_channel']]['ctl_name'];
        $arcurl = arcurl("home/{$ctl_name}/view", $res, true, SITE_URL);
        array_push($urlsArr, $arcurl);
    }
    if (0 < $typeid) {
        $res = M('arctype')->field('a.*')
            ->alias('a')
            ->find($typeid);
        $ctl_name = $channeltype_list[$res['current_channel']]['ctl_name'];
        $typeurl = typeurl("home/{$ctl_name}/lists", $res, true, SITE_URL);
        array_push($urlsArr, $typeurl);
    }

    $type = ('edit' == $type) ? 'update' : $type;
    $api = 'http://data.zz.baidu.com/'.$type.'?site='.DOMAIN.'&token='.$sitemap_zzbaidutoken;
    $ch = curl_init();
    $options =  array(
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urlsArr),
        CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
    );
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    
    return $result;    
}

/**
 * 自动生成引擎sitemap
 */
function sitemap_auto()
{
    $sitemap_config = tpCache('sitemap');
    if (isset($sitemap_config['sitemap_auto']) && $sitemap_config['sitemap_auto'] > 0) {
        sitemap_all();
    }
}

/**
 * 生成全部引擎sitemap
 */
function sitemap_all()
{
    sitemap_xml();
}

/**
 * 生成xml形式的sitemap
 */
function sitemap_xml()
{
    $sitemap_config = tpCache('sitemap');
    if (!isset($sitemap_config['sitemap_xml']) || empty($sitemap_config['sitemap_xml'])) {
        return '';
    }

    $modelu_name = 'home';
    $filename = ROOT_PATH . "sitemap.xml";

    /* 分类列表(用于生成列表链接的sitemap) */
    $map = array(
        'status'    => 1,
    );
    if (is_array($sitemap_config)) {
        // 过滤隐藏栏目
        if (isset($sitemap_config['sitemap_not1']) && $sitemap_config['sitemap_not1'] > 0) {
            $map['is_hidden'] = 0;
        }
        // 过滤外部模块
        if (isset($sitemap_config['sitemap_not2']) && $sitemap_config['sitemap_not2'] > 0) {
            $map['is_part'] = 0;
        }
    }
    $result_arctype = M('arctype')->field("*, id AS loc, add_time AS lastmod, 'daily' AS changefreq, '1.0' AS priority")
        ->where('status = 1')
        ->order('sort_order asc')
        ->getAllWithIndex('id');

    /* 文章列表(用于生成文章详情链接的sitemap) */
    $map = array(
        'arcrank'   => array('gt', -1),
        'status'    => 1,
    );
    if (is_array($sitemap_config)) {
        // 过滤外部模块
        if (isset($sitemap_config['sitemap_not2']) && $sitemap_config['sitemap_not2'] > 0) {
            $map['is_jump'] = 0;
        }
    }
    $field = "aid, channel, is_jump, jumplinks, add_time, update_time, typeid, aid AS loc, add_time AS lastmod, 'daily' AS changefreq, '1.0' AS priority";
    $result_archives = M('archives')->field($field)
        ->where($map)
        ->order('aid desc')
        ->limit(48000)
        ->select();

        // header('Content-Type: text/xml');//这行很重要，php默认输出text/html格式的文件，所以这里明确告诉浏览器输出的格式为xml,不然浏览器显示不出xml的格式
        $xml_wrapper = <<<XML
<?xml version='1.0' encoding='utf-8'?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
XML;

    $xml = simplexml_load_string($xml_wrapper);
    // $xml = new SimpleXMLElement($xml_wrapper);
     
    foreach ($result_arctype as $val) {
        $item = $xml->addChild('url'); //使用addChild添加节点
        if (is_array($val)) {
            foreach ($val as $key => $row) {
                if (in_array($key, array('loc','lastmod','changefreq','priority'))) {
                    if ($key == 'loc') {
                        if ($val['is_part'] == 1) {
                            $row = $val['typelink'];
                        } else {
                            $row = get_typeurl($val);
                        }
                        $row = str_replace('&amp;', '&', $row);
                        $row = str_replace('&', '&amp;', $row);
                    } elseif ($key == 'lastmod') {
                        $row = date('Y-m-d');
                    }
                    try {
                        $node = $item->addChild($key, $row);
                    } catch (Exception $e) {}
                    if (isset($attribute_array[$key]) && is_array($attribute_array[$key])) {
                        foreach ($attribute_array[$key] as $akey => $aval) {//设置属性值，我这里为空
                            $node->addAttribute($akey, $aval);
                        }
                    }
                }
            }
        }
    }

    foreach ($result_archives as $val) {
        $item = $xml->addChild('url'); //使用addChild添加节点
        if (is_array($val) && isset($result_arctype[$val['typeid']])) {
            $val = array_merge($result_arctype[$val['typeid']], $val);
            foreach ($val as $key => $row) {
                if (in_array($key, array('loc','lastmod','changefreq','priority'))) {
                    if ($key == 'loc') {
                        if ($val['is_jump'] == 1) {
                            $row = $val['jumplinks'];
                        } else {
                            $row = get_arcurl($val);
                        }
                        $row = str_replace('&amp;', '&', $row);
                        $row = str_replace('&', '&amp;', $row);
                    } elseif ($key == 'lastmod') {
                        $lastmod_time = empty($val['update_time']) ? $val['add_time'] : $val['update_time'];
                        $row = date('Y-m-d', $lastmod_time);
                    }
                    try {
                        $node = $item->addChild($key, $row);
                    } catch (Exception $e) {}
                    if (isset($attribute_array[$key]) && is_array($attribute_array[$key])) {
                        foreach ($attribute_array[$key] as $akey => $aval) {//设置属性值，我这里为空
                            $node->addAttribute($akey, $aval);
                        }
                    }
                }
            }
        }
    }

    $content = $xml->asXML(); //用asXML方法输出xml，默认只构造不输出。
    @file_put_contents($filename, $content);
}

/**
 * 获取栏目链接
 */
function get_typeurl($arctype_info = array())
{
    $ctl_name = '';
    $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
    if ($result) {
        $ctl_name = $result[$arctype_info['current_channel']]['ctl_name'];
    }
    $seoConfig = tpCache('seo');
    $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
    $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
    $typeurl = typeurl("home/{$ctl_name}/lists", $arctype_info, true, SITE_URL, $seo_pseudo, $seo_dynamic_format);
    // 自动隐藏index.php入口文件
    $typeurl = auto_hide_index($typeurl);

    return $typeurl;
}

/**
 * 获取文档链接
 */
function get_arcurl($arcview_info = array())
{
    $ctl_name = '';
    $result = model('Channeltype')->getAll('id, ctl_name', array(), 'id');
    if ($result) {
        $ctl_name = $result[$arcview_info['channel']]['ctl_name'];
    }
    $seoConfig = tpCache('seo');
    $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
    $seo_dynamic_format = !empty($seoConfig['seo_dynamic_format']) ? $seoConfig['seo_dynamic_format'] : config('ey_config.seo_dynamic_format');
    $arcurl = arcurl("home/{$ctl_name}/view", $arcview_info, true, SITE_URL, $seo_pseudo, $seo_dynamic_format);
    // 自动隐藏index.php入口文件
    $arcurl = auto_hide_index($arcurl);

    return $arcurl;
}

/**
 * 获取指定栏目的文档数
 */
function get_total_arc($typeid)
{
    $total = 0;
    $current_channel = M('arctype')->where('id', $typeid)->getField('current_channel');
    $allow_release_channel = config('global.allow_release_channel');
    if (in_array($current_channel, $allow_release_channel)) { // 能发布文档的模型
        $result = model('Arctype')->getHasChildren($typeid, $current_channel);
        $typeidArr = get_arr_column($result, 'id');
        $map = array(
            'typeid'    => array('IN', $typeidArr),
            'channel'    => array('eq', $current_channel),
        );
        $total = M('archives')->where($map)->count();
    } elseif ($current_channel == 8) { // 留言模型
        $total = M('guestbook')->where(array('typeid'=>array('eq', $typeid)))->count();
    }

    return $total;
}

/**
 * 将路径斜杆、反斜杠替换为冒号符，适用于IIS服务器在URL上的双重转义限制
 * @param string $filepath 相对路径
 * @param string $replacement 目标字符
 * @param boolean $is_back false为替换，true为还原
 */
function replace_path($filepath = '', $replacement = ':', $is_back = false)
{
    if (false == $is_back) {
        $filepath = str_replace(DIRECTORY_SEPARATOR, $replacement, $filepath);
        $filepath = preg_replace('#\/#', $replacement, $filepath);
    } else {
        $filepath = preg_replace('#'.$replacement.'#', '/', $filepath);
        $filepath = str_replace('//', ':/', $filepath);
    }
    return $filepath;
}

/**
 * 获取当前栏目的第一级栏目
 */
function gettoptype($typeid, $field = 'typename')
{
    $parent_list = model('Arctype')->getAllPid($typeid); // 获取当前栏目的所有父级栏目
    $result = current($parent_list); // 第一级栏目
    if (isset($result[$field]) && !empty($result[$field])) {
        return $result[$field];
    } else {
        return '';
    }
}

/**
 * URL模式下拉列表
 */
function get_seo_pseudo_list($key = '')
{
    $data = array(
        1   => '动态URL',
        // 2   => '静态页面',
        3   => '伪静态化'
    );

    return isset($data[$key]) ? $data[$key] : $data;
}

/**
 * 对指定的操作系统获取目录的所有组与所有者
 * @param string $path 目录路径
 * @return array
 */
function get_chown_pathinfo($path = '') 
{
    $pathinfo = true;

    if (function_exists('stat')) {
        /*指定操作系统，在列表内才进行后续获取*/
        $isValidate = false;
        $os = PHP_OS;
        $osList = array('linux','unix');
        foreach ($osList as $key => $val) {
            if (stristr($os, $val)) {
                $isValidate = true;
                continue;
            }
        }
        /*--end*/

        if (true === $isValidate) {
            $path = !empty($path) ? $path : ROOT_PATH;
            $stat = stat($path);
            if (function_exists('posix_getpwuid')) {
                $pathinfo = posix_getpwuid($stat['uid']); 
            } else {
                $pathinfo = array(
                    'name'  => (0 == $stat['uid']) ? 'root' : '',
                    'uid'  => $stat['uid'],
                    'gid'  => $stat['gid'],
                );
            }
        }
    }

    return $pathinfo;
}

/**
 * URL中隐藏index.php入口文件（适用后台显示前台的URL）
 */
function auto_hide_index($url) {
    $web_adminbasefile = tpCache('web.web_adminbasefile');
    $web_adminbasefile = !empty($web_adminbasefile) ? $web_adminbasefile : '/login.php';
    $url = str_replace($web_adminbasefile, '/index.php', $url);
    $seo_inlet = config('ey_config.seo_inlet');
    if (1 == $seo_inlet) {
        $url = str_replace('/index.php/', '/', $url);
    }
    return $url;
}

/*组装成层级下拉列表框*/
function menu_select($selected = 0)
{
    $select_html = '';
    $menuArr = getAllMenu();
    if (!empty($menuArr)) {
        foreach ($menuArr AS $key => $val)
        {
            $select_html .= '<option value="' . $val['id'] . '" data-grade="' . $val['grade'] . '"';
            $select_html .= ($selected == $val['id']) ? ' selected="ture"' : '';
            if (!empty($val['child'])) {
                $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
            }
            $select_html .= '>';
            if ($val['grade'] > 0)
            {
                $select_html .= str_repeat('&nbsp;', $val['grade'] * 4);
            }
            $name = !empty($val['name']) ? $val['name'] : '默认';
            $select_html .= htmlspecialchars(addslashes($name)) . '</option>';

            if (empty($val['child'])) {
                continue;
            }
            foreach ($menuArr[$key]['child'] as $key2 => $val2) {
                $select_html .= '<option value="' . $val2['id'] . '" data-grade="' . $val2['grade'] . '"';
                $select_html .= ($selected == $val2['id']) ? ' selected="ture"' : '';
                if (!empty($val2['child'])) {
                    $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
                }
                $select_html .= '>';
                if ($val2['grade'] > 0)
                {
                    $select_html .= str_repeat('&nbsp;', $val2['grade'] * 4);
                }
                $select_html .= htmlspecialchars(addslashes($val2['name'])) . '</option>';

                if (empty($val2['child'])) {
                    continue;
                }
                foreach ($menuArr[$key]['child'][$key2]['child'] as $key3 => $val3) {
                    $select_html .= '<option value="' . $val3['id'] . '" data-grade="' . $val3['grade'] . '"';
                    $select_html .= ($selected == $val3['id']) ? ' selected="ture"' : '';
                    if (!empty($val3['child'])) {
                        $select_html .= ' disabled="true" style="background-color:#f5f5f5;"';
                    }
                    $select_html .= '>';
                    if ($val3['grade'] > 0)
                    {
                        $select_html .= str_repeat('&nbsp;', $val3['grade'] * 4);
                    }
                    $select_html .= htmlspecialchars(addslashes($val3['name'])) . '</option>';
                }
            }
        }
    }

    return $select_html;
}