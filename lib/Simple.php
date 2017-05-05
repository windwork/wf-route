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
 * Windwork简单路由实现
 *
 * @package     wf.route
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.route.html
 * @since       0.1.0
 */
class Simple implements RouteInterface 
{
    /**
     * 
     * @var \wf\route\RouteVO
     */
    protected $vo;
    
    /**
     * 配置信息
     * @var array
    */
    public $cfg = [
        'useModule'   => 0,  // 是否启用模块
        
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
        if($cfg) {
            $this->setCfg($cfg);
        } 
    }
    
    /**
     * 初始化路由实体
     */
    protected function initRouteVO() 
    {
        $this->vo = new \wf\route\RouteVO();
        
        // 启用模块则设置默认模块
        if ($this->cfg['useModule']) {
            $this->vo->mod = $this->cfg['defaultMod'];
        }
        
        // 设置默认控制器
        $this->vo->ctl = $this->cfg['defaultCtl'];
        
        // 设置默认操作
        $this->vo->act = $this->cfg['defaultAct'];
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \wf\route\RouteInterface::getRouteVO()
     */
    public function getRouteVO() 
    {
        return $this->vo;
    }    

    /**
     * 分析站内URL
     * 
     * 取得链接应该映射到哪个模块、控制器、动作，并且传什么参数到动作方法中。
     * 
     * @param string $uri
     * @return \wf\route\RouteInterface
     */
    public function parse($uri) 
    {
        $this->initRouteVO();
        $opts = &$this->cfg;
        
        // 取得index.php?及后面部分
        // 去掉域名
        $uri = preg_replace("/((http|https)\\:\\/\\/.*?\\/)/", '', $uri); 
        $uri = trim($uri, '/');
        
        // 去掉站点根目录路径.
        $basePath = trim($opts['basePath'], '/');
        if($basePath && $basePath === substr($uri, 0, strlen($basePath))){
            $uri = substr($uri, strlen($basePath));
            $uri = trim($uri, '/');
        }
        
        // no rewrite: {$opts['scriptName']}?$mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext&$query#$fragment
        // rewrite: $mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext?$query#$fragment
        
        // 去掉index.php?或index.php/
        if($uri && $opts['scriptName'] === substr($uri, 0, strlen($opts['scriptName']))){
            $uri = substr($uri, strlen($opts['scriptName']));
        }
        $uri = trim($uri, './?');
        
        if (!$uri) {
            // 首页
            $this->buildVOCtlClass();            
            return $this;
        }

        // 将uri统一为：$mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext&$query#fragment
        $uri && $uri = str_replace('?', '&', $uri);

        // 提取锚，并把锚串从uri中去掉
        if(false !== $pos = strpos($uri, '#')) {
            $this->vo->anchor = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }

        // 提取常规查询串参数，并把查询串从uri中去掉
        if (false !== $pos = strpos($uri, '&')) {
            $this->vo->query = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }
        
        // 去掉伪静态后缀
        if($opts['rewriteExt'] && $opts['rewriteExt'] === substr($uri,  - strlen($opts['rewriteExt']))) {
            $uri = substr($uri, 0,  - strlen($opts['rewriteExt']));
        }
        
        // URI解码
        if ($opts['encode'] && preg_match("/q_(.+)/", $uri, $mat)) {
            // base64编码的变体，+/字符使用方便URL中传输的-_字符
            $uri = base64_decode(strtr($mat[1], '-_', '+/'));
        }
        
        // /name:value 键值对变量
        if(preg_match_all("#/([^/&]+?\\:([^/&\\?]|$)*)#", $uri, $match)) {
            // 提取变量
            $attrStr = str_replace(':', '=', implode('&', $match[1]));
            parse_str($attrStr, $attributes);
            
            $this->vo->attributes = $attributes;

            $uri = preg_replace("#/[^/&]+?\\:([^/&\\?]|$)*#", '', $uri);
        }
        
        // 请求参数
        $actArgs = explode("/", $uri);
        
        // 提取mod,ctl,act
        // 如果第一个/前只包含字母、数字、下划线和点符号，则是合法的路由ID
        if (!preg_match("/[^a-z0-9_\\.]+/i", $actArgs[0])) {
            $routeId = array_shift($actArgs);
            
            // $routeId没有.则可能是简化URL后的$route
            if (false === strpos($routeId, '.')) {
                // 简短url还原
                $routeKey = strtolower($routeId);
                if(array_key_exists($routeKey, $opts['alias'])) {
                    $routeId = $opts['alias'][$routeKey];                    
                    // $routeId中有 / 则需要重新取得$actArgs、$routeId参数
                    if (false !== stripos($routeId, '/')) {
                        array_unshift($actArgs, $routeId);
                        $actArgs = explode("/", implode('/', $actArgs));
                        $routeId = array_shift($actArgs);
                    }
                }
            }
            
            $routeArr = explode('.', $routeId);

            // 如果启用模块则提取模块，则提取第一个点号前面的模块名
            if ($opts['useModule']) {
                $this->vo->mod = strtolower(array_shift($routeArr));
            }
            
            // 如果acttion不为空，则取最后一个点号后面的action名
            if(isset($routeArr[1])) {
                // 
                $this->vo->act = strtolower(array_pop($routeArr)); // else = defaultAct
            }            
            
            // 取控制器类标识
            if ($routeArr) {
                $this->vo->ctl = strtolower(join('.', $routeArr)); // else = defaultCtl
            }
        } // else mod = defaultMod
        
        // action参数
        $this->vo->actParams = $actArgs;

        $this->buildVOCtlClass();
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \wf\route\RouteInterface::toUrl()
     */
    public function toUrl($fullUrl = false) 
    {
        $uri = trim("{$this->vo->mod}.{$this->vo->ctl}.{$this->vo->act}", '.');
        if ($this->vo->actParams) {
            $uri .= '/' . implode('/', $this->vo->actParams);
        }
        $url = $this->buildUrL($uri, $this->vo->attributes, $this->vo->query, $this->vo->anchor, $fullUrl);
        return $url;
    }

    /**
     * 生成URL
     * @param string $uri $mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...]?$query#$fragment
     * @param array $vars = [] 是否生成完整URL，包含域名和根目录路径
     * @param bool $fullUrl = false 是否生成完整URL，包含域名和根目录路径
     * @return string
     */
    public function createUrl($uri, array $vars = [], $fullUrl = false) 
    {
        // 构造URL： $mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext?$query#$fragment
        $query = '';
        $fragment = '';
        
        // 提取锚，并把url中的锚去掉，构造好url后再添加上
        if(false !== $pos = strpos($uri, '#')) {
            $fragment = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }
        
        // 提取查询串参数
        if(false !== $pos = strpos($uri, '?')) {
            $query = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }

        $url = $this->buildUrL($uri, $vars, $query, $fragment, $fullUrl);
        
        return $url;
    }

    /**
     * 生成URL
     * @param string $uri  $mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...]
     * @param array $vars = []  参数
     * @param string $query = '' 查询串
     * @param string $fragment = ''  锚点
     * @param bool $fullUrl = false 是否生成完整URL，包含域名和根目录路径
     * @return string
     */
    private function buildUrL($uri, array $vars = [], $query = '', $fragment = '', $fullUrl = false) 
    {
        // 构造URL： $mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext?$query#$fragment
        $url = trim($uri, '/');
        
        // URL简短化前从URL中获取mod,ctl
        $mod = '';
        $ctl = '';
        if (preg_match("/(.+?)\\.(.+)\\.(.+?)/", strtolower($url), $mat)) {
            $mod = $mat[1];
            $ctl = $mat[2];
        } elseif (preg_match("/(.+?)\\.(.+)/", strtolower($url), $mat)) {
            $ctl = $mat[1];
        }

        // URL简短化
        foreach ($this->cfg['alias'] as $shortTag => $rule) {
            $rule = trim($rule, '/');
            if (stripos($url.'/', $rule.'/') === 0) {
                $url = $shortTag . substr($url, strlen($rule));
                break;
            }
        }
        
        // 增加变量
        if (!empty($vars)) {
            $url .= '/' . str_replace("=", ':', http_build_query($vars, '', '/'));
        }
        
        // 对URL进行编码
        if ($this->cfg['encode']) {
            $url = 'q_' . strtr(base64_encode($url), '+/', '-_');
            $url = rtrim($url, '=');
        }
        
        // 加上伪静态后缀（不论是否启用URL Rewrite）
        $url .= $this->cfg['rewriteExt'];
        
        // 未启用url重写，补充base url
        if(!$this->cfg['rewrite']) {
            $url = "{$this->cfg['scriptName']}?{$url}";
        }
        
        $modAndCtl = trim("{$mod}.{$ctl}", '.');
        
        if ($modAndCtl && isset($this->cfg['domain'][$modAndCtl])) {
            // 控制器指定域名
            $url = rtrim($this->cfg['basePath'], '/') . '/' . trim($url, '/');
            $url = rtrim($this->cfg['domain'][$modAndCtl], '/') . '/' . trim($url, '/');
        } elseif ($mod && isset($this->cfg['domain'][$mod])) {
            // 模块指定域名
            $url = rtrim($this->cfg['basePath'], '/') . '/' . trim($url, '/');
            $url = rtrim($this->cfg['domain'][$mod], '/') . '/' . trim($url, '/');
        } elseif ($fullUrl || $this->cfg['fullUrl']) {
            // 带域名的完整URL
            $url = rtrim($this->cfg['basePath'], '/') . '/' . trim($url, '/');
            $url = rtrim($this->cfg['hostInfo'], '/') . '/' . trim($url, '/');
        }

        // 查询串
        if (!empty($query)) {
            $url .= (strpos($url, '?') ? '&' : '?') . $query;
        }
        
        // 还原锚
        if (!empty($fragment)) {
            $url .= '#' . $fragment;
        }
        
        return $url;
    }
    
    /**
     * 设置ruoter配置参数
     * @param array $cfg
     */
    private function setCfg(array $cfg) 
    {
        // 从siteUrl提取hostInfo、basePath参数
        if (!empty($cfg['siteUrl']) && (empty($cfg['hostInfo']) || empty($cfg['basePath']))) {
            // siteUrl格式检查
            if (!preg_match("/^(http[s]?:\\/\\/.+?)(\\/.*)/i", $cfg['siteUrl'], $siteUrlMatch)) {
                throw new Exception('siteUrl参数格式不是http/https网址！');
            }

            $cfg['hostInfo'] = $siteUrlMatch[1];
            $cfg['basePath'] = $siteUrlMatch[2];
        }
        
        $this->cfg = array_replace_recursive($this->cfg, $cfg);
                
        return $this;
    }


    /**
     * 根据vo的mod、ctl属性生成vo类的控制器类名属性 ctlClass
     * 控制器类名命名规范：首字母大写，后面加上Controller，其它字母都是小写
     * @return string
     */
    protected function buildVOCtlClass() 
    {
        $mod = $this->vo->mod;
        $ctl = $this->vo->ctl;
        
        if(empty($mod)) {
            // 不启用模块
            $ns = "\\app\\controller";
        } else {
            // 启用模块
            $ns = "\\app\\{$mod}\\controller";
        }
        
        if (strpos($ctl, '.')) {
            // 控制器类放在controller文件夹的子文件夹
            $name = substr($ctl, strrpos($ctl, '.'));
            $subNS = substr($ctl, 0, strrpos($ctl, '.'));
            $ns .= '\\' . strtr($subNS, '.', '\\');
        } else {
            // 控制器类放在controller文件夹
            $name = $ctl;
        }
    
        $this->vo->ctlClass = $ns . '\\' . ucfirst($name) . 'Controller';
    }
    
}
