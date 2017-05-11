<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
 * 
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */
namespace wf\route;

/**
 * Windwork路由抽象类
 * 
 * @package     wf.route
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.route.html
 * @since       0.1.0
 */
abstract class RouteAbstract
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
    
    /**
     * 通过构造函数设置配置信息
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        
    }

    /**
     * 解析链接，取得路由参数
     * @param string $uri
     * @return \wf\route\RouteAbstract
     */
    abstract public function parse($uri);
    
    /**
     * 生成遵循路由规则的URL
     * @param string $uri
     * @param array $args = []
     * @param bool $isFullUrl = false 是否返回带域名的完整URL
     * @return string
     */
    abstract public function createUrl($uri, array $args = [], $isFullUrl = false);
    
    /**
     * 将路由实例转成遵循路由规则的URL，可将解析的非规范URL转成规范URL
     * @param bool $isFullUrl = false 是否返回带域名和根目录路径的完整URL
     * @return string
     */
    abstract public function toUrl($isFullUrl = false);
}