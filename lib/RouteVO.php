<?php

namespace wf\route;

/**
 * 路由解析参数规范类
 * http(s)://domain:port/path/$app.$ctl.$act/$act_params1/$act_params2[/...]/$key1:$val1/$key2:$val2[/...]?query#fragment
 * 
 * @package     wf.route
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.route.html
 * @since       0.1.0
 */
class RouteVO 
{    
    /**
     * 控制器类名（带命名空间）
     * 转发器将根据该属性创建控制器类实例
     * @var string
     */
    public $ctlClass = '';
    
    /**
     * 模块ID
     * @var string
     */
    public $mod = '';
    
    /**
     * 控制器ID
     * @var string
     */
    public $ctl = '';
    
    /**
     * 操作ID
     * @var string
     */
    public $act = '';
    
    /**
     * 操作方法参数，每个元素所谓一个参数传递到action方法的参数
     * @var array
     */
    public $actParams = [];
    
    /**
     * 请求键值对参数
     * @var array
     */
    public $attributes = [];
    
    /**
     * 查询串
     * @var string
     */
    public $query = '';

    /**
     * 锚点值
     * @var string
     */
    public $anchor = '';
}
