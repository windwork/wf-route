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
 * <b>WF Route Interface</b>
 * 
 * @package     wf.route
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.route.html
 * @since       0.1.0
 */
interface RouteInterface 
{
    /**
     * 通过构造函数设置配置信息
     * @param array $options
     */
    public function __construct(array $options = []);
    
    /**
     * 路由实例解析URL后可获取路由实体对象
     * @return \wf\route\RouteVO
     */
    public function getRouteVO();

    /**
     * 解析链接，取得路由参数
     * @param string $uri
     * @return \wf\route\RouteInterface
     */
    public function parse($uri);
    
    /**
     * 生成遵循路由规则的URL
     * @param string $uri
     * @param array $args = []
     * @param bool $isFullUrl = false 是否返回带域名的完整URL
     * @return string
     */
    public function createUrl($uri, array $args = [], $isFullUrl = false);
    
    /**
     * 将路由实例转成遵循路由规则的URL，可将解析的非规范URL转成规范URL
     * @param bool $isFullUrl = false 是否返回带域名和根目录路径的完整URL
     * @return string
     */
    public function toUrl($isFullUrl = false);
}