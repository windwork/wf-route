<?php
/**
 * Windwork
 * 
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
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
     * 配置信息
     * @var array
     */
    public $cfg = [
        'useModule'   => 1,  // 是否启用模块
    
        'defaultMod'  => 'common',   // 默认模块，仅启用模块后有效
        'defaultCtl'  => 'default',  // 默认控制器
        'defaultAct'  => 'index',    // 默认action
    
        'rewrite'     => 1,          // 是否启用URLRewrite
        'rewriteExt'  => '',         // URL重写链接后缀，如：.html
        'fullUrl'     => 0,          // 是否使用完整URL（http://开头）
        'encode'      => 0,          // 是否对链接参数进行编码，一般不想让用户直接看到链接参数则启用
    
        // 入口文件名
        'scriptName'  => 'index.php',
    
        // 站点首页网址
        'siteUrl'     => '',
    
        // 站点域名（可自动从siteUrl参数中提取），如：http://www.yoursite.com
        'hostInfo'    => '',
    
        // 站点目录（可自动从siteUrl参数中提取），如：/ctx/
        'basePath'    => '',
    
        // 模块/控制器指定域名
        'domain'      => [],
    
        // URL简写规则
        'alias'       => [],
    ];
    
    /**
     * 构造函数设置配置信息
     * @param array $cfg = []
     */
    public function __construct(array $cfg = [])
    {
        if(!$cfg) {
            return ;
        }
        
        // 从siteUrl提取hostInfo、basePath参数
        if (!empty($cfg['siteUrl']) && (empty($cfg['hostInfo']) || empty($cfg['basePath']))) {
            // siteUrl格式检查
            if (!preg_match("/^(http[s]?:\\/\\/.+?)(\\/.*)/i", $cfg['siteUrl'], $siteUrlMatch)) {
                throw new \wf\route\Exception('siteUrl参数格式不是http/https网址！');
            }
            
            $cfg['hostInfo'] = $siteUrlMatch[1];
            $cfg['basePath'] = $siteUrlMatch[2];
        }
        
        $this->cfg = array_replace_recursive($this->cfg, $cfg);
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