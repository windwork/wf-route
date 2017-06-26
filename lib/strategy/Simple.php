<?php
/**
 * Windwork
 * 
 * 一个用于快速开发高并发Web应用的轻量级PHP框架
 * 
 * @copyright Copyright (c) 2008-2017 Windwork Team. (http://www.windwork.org)
 * @license   http://opensource.org/licenses/MIT
 */
namespace wf\route\strategy;

/**
 * Windwork简单路由实现
 *
 * @package     wf.route.strategy
 * @author      cm <cmpan@qq.com>
 * @link        http://docs.windwork.org/manual/wf.route.html
 * @since       0.1.0
 */
class Simple extends \wf\route\RouteAbstract
{
    
    /**
     * 初始化路由实体
     */
    protected function initRoutArg() 
    {        
        // 启用模块则设置默认模块
        if ($this->cfg['useModule']) {
            $this->mod = $this->cfg['defaultMod'];
        }
        
        // 设置默认控制器
        $this->ctl = $this->cfg['defaultCtl'];
        
        // 设置默认操作
        $this->act = $this->cfg['defaultAct'];
    }

    /**
     * 分析站内URL
     * 
     * 取得链接应该映射到哪个模块、控制器、动作，并且传什么参数到动作方法中。
     * 
     * @param string $uri
     * @return \wf\route\RouteAbstract
     */
    public function parse($uri) 
    {
        $this->initRoutArg();
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
            $this->buildCtlClass();            
            return $this;
        }

        // 将uri统一为：$mod.$ctl.$act/$act_params/$key1:$val1/$key2:$val2[/...].$ext&$query#fragment
        $uri && $uri = str_replace('?', '&', $uri);

        // 提取锚，并把锚串从uri中去掉
        if(false !== $pos = strpos($uri, '#')) {
            $this->anchor = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }

        // 提取常规查询串参数，并把查询串从uri中去掉
        if (false !== $pos = strpos($uri, '&')) {
            $this->query = substr($uri, $pos + 1);
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
            
            $this->attributes = $attributes;

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
                $this->mod = strtolower(array_shift($routeArr));
            }
            
            // 如果acttion不为空，则取最后一个点号后面的action名
            if(isset($routeArr[1])) {
                // 
                $this->act = strtolower(array_pop($routeArr)); // else = defaultAct
            }            
            
            // 取控制器类标识
            if ($routeArr) {
                $this->ctl = strtolower(join('.', $routeArr)); // else = defaultCtl
            }
        } // else mod = defaultMod
        
        // action参数
        $this->actParams = $actArgs;

        $this->buildCtlClass();
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \wf\route\RouteAbstract::toUrl()
     */
    public function toUrl($fullUrl = false) 
    {
        $uri = trim("{$this->mod}.{$this->ctl}.{$this->act}", '.');
        if ($this->actParams) {
            $uri .= '/' . implode('/', $this->actParams);
        }
        $url = $this->buildUrL($uri, $this->attributes, $this->query, $this->anchor, $fullUrl);
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
        $url && $url .= $this->cfg['rewriteExt'];
        
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
            if ($this->cfg['siteUrl']) {
                $url = rtrim($this->cfg['siteUrl'], '/') . '/' . trim($url, '/');
            } else {
                $url = rtrim($this->cfg['basePath'], '/') . '/' . trim($url, '/');
                $url = rtrim($this->cfg['hostInfo'], '/') . '/' . trim($url, '/');
            }
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
     * 根据mod、ctl属性生成控制器类名属性 ctlClass
     * 控制器类名命名规范：首字母大写，后面加上Controller，其它字母都是小写
     * @return string
     */
    protected function buildCtlClass() 
    {
        $mod = $this->mod;
        $ctl = $this->ctl;
        
        if(empty($mod)) {
            // 不启用模块
            $ns = "\\app\\controller";
        } else {
            // 启用模块
            $ns = "\\app\\{$mod}\\controller";
        }
        
        if (strpos($ctl, '.')) {
            // 控制器类放在controller文件夹的子文件夹
            $name = substr($ctl, strrpos($ctl, '.') + 1);
            $subNS = substr($ctl, 0, strrpos($ctl, '.'));
            $ns .= '\\' . strtr($subNS, '.', '\\');
        } else {
            // 控制器类放在controller文件夹
            $name = $ctl;
        }
    
        $this->ctlClass = $ns . '\\' . ucfirst($name) . 'Controller';
    }
    
}
