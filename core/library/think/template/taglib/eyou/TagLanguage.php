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

namespace think\template\taglib\eyou;

/**
 * 多语言列表
 */
class TagLanguage extends Base
{
    //初始化
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取多语言列表
     * @author 小虎哥 by 2018-4-20
     */
    public function getLanguage($type = 'default', $limit = '')
    {
        $map = ['status'=>1];
        if ('default' == $type) {
            $map['mark'] = ['NEQ', $this->home_lang];
        }
        $result = M("language")->where($map)
            ->order('sort_order asc')
            ->limit($limit)
            // ->cache(true,EYOUCMS_CACHE_TIME,"language")
            ->select();

        /*去掉入口文件*/
        $inletStr = '/index.php';
        $seo_inlet = config('ey_config.seo_inlet');
        1 == intval($seo_inlet) && $inletStr = '';
        /*--end*/

        foreach ($result as $key => $val) {
            $val['target'] = ($val['target'] == 1) ? 'target="_blank"' : 'target="_self"';
            $val['logo'] = get_default_pic("/public/static/common/images/language/{$val['mark']}.gif");

            /*单独域名*/
            $url = $val['url'];
            if (empty($url)) {
                if (1 == $val['is_home_default']) {
                    $url = $this->root_dir.'/'; // 支持子目录
                } else {
                    $seoConfig = tpCache('seo', [], $val['mark']);
                    $seo_pseudo = !empty($seoConfig['seo_pseudo']) ? $seoConfig['seo_pseudo'] : config('ey_config.seo_pseudo');
                    if (1 == $seo_pseudo) {
                        $url = request()->domain().$this->root_dir.$inletStr; // 支持子目录
                        if (!empty($inletStr)) {
                            $url .= '?';
                        } else {
                            $url .= '/?';
                        }
                        $url .= http_build_query(['lang'=>$val['mark']]);
                    } else {
                        $url = $this->root_dir.$inletStr.'/'.$val['mark']; // 支持子目录
                    }
                }
            }
            $val['url'] = $url;
            /*--end*/

            $result[$key] = $val;
        }

        return $result;
    }
}