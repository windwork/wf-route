<?php

require_once __DIR__ . '/../lib/RouteAbstract.php';
require_once __DIR__ . '/../lib/strategy/Simple.php';
require_once __DIR__ . '/../lib/Exception.php';

use \wf\route\RouteAbstract;
use \wf\route\strategy\Simple;

/**
 * RouteSimple test case.
 */
class RouteSimpleTest extends PHPUnit_Framework_TestCase {
    
    /**
     *
     * @var Simple
     */
    private $routeSimple;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp() {
        parent::setUp();        
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown() {
        // TODO Auto-generated RouteSimpleTest::tearDown()
        $this->routeSimple = null;
        
        parent::tearDown();
    }
    
    /**
     * 解析链接，取得路由参数
     * @param string $uri
     * @return \wf\route\RouteAbstract
     */
    public function testParse() {
        $cfgs = [
            'useModule'        => 0,       // 是否启用模块
            
            'defaultMod'       => 'common',   // 默认模块
            'defaultCtl'       => 'default',  // 默认控制器
            'defaultAct'       => 'index',    // 默认action
            
            'rewrite'          => 1,       // 启用URLRewrite
            'rewriteExt'       => '.html', // 链接后缀，如：.html
            'fullUrl'          => 0,       // 是否使用完整URL（http://开头）
            'encode'           => 0,       // 是否对链接参数进行编码
            
            'siteUrl'          => 'https://www.my.com/demo/',
            
            'alias' => [
                'hi' => 'welcome.main.hello',
                'about' => 'article.detail/about/us',
                'login' => 'user.main.login',
            ],
            
            'domain'      => [
            ],
        ];
                
        $routeObj = new Simple($cfgs);
        
        // 默认首页
        $routeObj->parse('/');
        $this->assertEquals($routeObj->mod, '');
        $this->assertEquals($routeObj->ctl, $cfgs['defaultCtl']);
        $this->assertEquals($routeObj->act, $cfgs['defaultAct']);
        
        // article.detail/about/us （在配置中设置简写为about）
        $routeObj->parse('about.html');
        
        $uri = "{$routeObj->ctl}.{$routeObj->act}";
        $this->assertEquals('article.detail', $uri);
        
        // welcome.main.hello（在配置中设置简写为hi）
        $uri = 'https://www.my.com/demo/hi/yes/i/do.html';
        $routeObj->parse($uri);
        
        $rUri1 = $routeObj->toUrl();
        $this->assertEquals('hi/yes/i/do.html', $rUri1);
        
        // ctl/act
        $uri = "{$routeObj->ctl}.{$routeObj->act}"; 
        $this->assertEquals('welcome.main.hello', $uri);
        // actParams
        $this->assertEquals('yes/i/do', implode('/', $routeObj->actParams));
        
        // 使用模块
        $cfgs['useModule'] = 1;
        $routeObj = new Simple($cfgs);
        
        $uri = 'https://www.my.com/demo/hi.html';
        $routeObj->parse($uri);
        
        $uri = 'https://www.my.com/demo/user.auth.login/type:wx/vip:1.html?page=1#axx';
        $routeObj->parse($uri);
        
        print "\n4===========\n";
        print_r($routeObj);
        
        $uri = 'https://www.my.com/demo/user.manage.account.list/dosth/dosth2/page:1/rows:50?aa=aab&bb=bbb#auxx';
        $routeObj->parse($uri);
        
        print "\n5===========\n";
        print_r($routeObj);
        
        $this->assertEquals($routeObj->mod, 'user');
        $this->assertEquals($routeObj->ctl, 'manage.account');
        $this->assertEquals($routeObj->act, 'list');
        $this->assertEquals($routeObj->query, 'aa=aab&bb=bbb');
        $this->assertEquals($routeObj->anchor, 'auxx');
        $this->assertEquals($routeObj->actParams[0], 'dosth');
        $this->assertEquals($routeObj->actParams[1], 'dosth2');
        $this->assertEquals($routeObj->attributes['page'], 1);
        $this->assertEquals($routeObj->attributes['rows'], 50);

        // 不使用URL重写
        $cfgs['rewrite'] = 0;
        $routeObj = new Simple($cfgs);
        
        $uri = 'https://www.my.com/demo/index.php?hi/you/are/page:1/rows:20#ahxxx';
        $routeObj->parse($uri);
        
        $this->assertEquals($routeObj->mod, 'welcome');
        $this->assertEquals($routeObj->ctl, 'main');
        $this->assertEquals($routeObj->act, 'hello');
        $this->assertEquals($routeObj->attributes['page'], 1);
        $this->assertEquals($routeObj->attributes['rows'], 20);
        $this->assertEquals($routeObj->actParams[0], 'you');
        $this->assertEquals($routeObj->actParams[1], 'are');
        
        // 从router实例再转回URL
        $toUrl = $routeObj->toUrl(1);
        $exp = 'https://www.my.com/demo/index.php?hi/you/are/page:1/rows:20.html#ahxxx';
        $this->assertEquals($exp, $toUrl);

        $uri = 'https://www.my.com/demo/app.php/hi/you/are/page:1/rows:20#ahxxx';
        $exp = 'https://www.my.com/demo/app.php?hi/you/are/page:1/rows:20.html#ahxxx';
        $routeObj = new Simple(array_replace_recursive($cfgs, ['scriptName' => 'app.php']));
        $routeObj->parse($uri);
        $toUrl = $routeObj->toUrl(1);
        $this->assertEquals($exp, $toUrl);
        
        $cfgs['encode'] = 1;
        $routeObj = new Simple($cfgs);
        $routeObj->parse('q_aGkvbGFuZzp6aC1DTi90aXRsZTolRTQlQjglQUQlRTYlOTYlODc.html');
                
        $this->assertEquals($routeObj->mod, 'welcome');
        $this->assertEquals($routeObj->ctl, 'main');
        $this->assertEquals($routeObj->act, 'hello');
        $this->assertEquals($routeObj->query, '');
        $this->assertEquals($routeObj->anchor, '');
        $this->assertEquals($routeObj->attributes['lang'], 'zh-CN');

        $routeObj->parse('https://www.my.com/demo/q_aGkvbGFuZzp6aC1DTi90aXRsZTolRTQlQjglQUQlRTYlOTYlODc.html');
        
        $this->assertEquals($routeObj->mod, 'welcome');
        $this->assertEquals($routeObj->ctl, 'main');
        $this->assertEquals($routeObj->act, 'hello');
        $this->assertEquals($routeObj->query, '');
        $this->assertEquals($routeObj->anchor, '');
        $this->assertEquals($routeObj->attributes['lang'], 'zh-CN');
        
        $uri = 'https://www.my.com/demo/index.php?hi/you/are/page:1/rows:20#ahxxx';
        $routeObj->parse($uri);
        
        $this->assertEquals($routeObj->mod, 'welcome');
        $this->assertEquals($routeObj->ctl, 'main');
        $this->assertEquals($routeObj->act, 'hello');
        
        $url = $routeObj->toUrl();
        $this->assertEquals('index.php?q_aGkveW91L2FyZS9wYWdlOjEvcm93czoyMA.html#ahxxx', $url);
    }
    
    /**
     * 生成遵循路由规则的URL
     * @param array $info
     * @param array $query
     * @param array $query
     * @return bool
     */
    public function testCreateUrl() {
        $cfgs = [
            'useModule'   => 0,  // 是否启用模块
            
            'defaultMod'  => 'common',   // 默认模块
            'defaultCtl'  => 'default',  // 默认控制器
            'defaultAct'  => 'index',    // 默认action
            
            'rewrite'     => 0,          // 是否启用URLRewrite
            'rewriteExt'  => '.html',    // URL重写链接后缀，如：.html
            'fullUrl'     => 0,          // 是否使用完整URL（http://开头）
            'encode'      => 0,          // 是否对链接参数进行编码，一般不想让用户直接看到链接参数则启用
    
            // 入口文件名
            'scriptName'  => 'index.php',
            
            // 站点首页网址
            'siteUrl'     => 'https://www.yoursite.com/demo/',
            
            // 模块/控制器指定域名
            'domain'      => [],
            
            // URL简写规则
            'alias'       => [],
        ];
        
        $router = new Simple($cfgs);
        
        // 生成URL
        // 未启用URL重写链接
        $exp1 = 'index.php?goods.detail/5.html';
        $url1 = $router->createUrl('goods.detail/5');
        $this->assertEquals($exp1, $url1);

        // 未重写完整URL
        $exp2 = 'https://www.yoursite.com/demo/index.php?goods.detail/5.html';
        $url2 = $router->createUrl('goods.detail/5', [], 1);
        $this->assertEquals($exp2, $url2);

        // 启用URL重写链接
        $cfgs['rewrite'] = 1;
        $router = new Simple($cfgs);
        $exp3 = 'goods.detail/5.html';
        $url3 = $router->createUrl('goods.detail/5');
        $this->assertEquals($exp3, $url3);

        // 键值对参数 k1:v1/k2:v2
        $exp3 = 'goods.detail/5/k1:v1/k2:v2.html';
        $url3 = $router->createUrl('goods.detail/5/k1:v1/k2:v2');
        $this->assertEquals($exp3, $url3);
        
        // 中文键值对参数被url_encode
        $exp3 = 'goods.detail/5/lang:zh_CN/city:%E5%8C%97%E4%BA%AC.html';
        $url3 = $router->createUrl('goods.detail/5', ['lang' => 'zh_CN', 'city' => '北京']);
        $this->assertEquals($exp3, $url3);
        
        // 重写完整URL
        $exp4 = 'https://www.yoursite.com/demo/goods.detail/5.html';
        $url4 = $router->createUrl('goods.detail/5', [], 1);
        $this->assertEquals($exp4, $url4);
        
        // URL简短化
        $cfgs['alias']['reg'] = 'member.account.register';
        $cfgs['alias']['logout'] = 'member.auth.logout';

        $router = new Simple($cfgs);
        
        // member.account.register.html 被简化成reg.html
        $exp5 = 'reg.html'; 
        $url5 = $router->createUrl('member.account.register');
        $this->assertEquals($exp5, $url5);

        // member.auth.logout.html 被简化成logout.html
        $exp6 = 'logout.html';
        $url6 = $router->createUrl('member.auth.logout');
        $this->assertEquals($exp6, $url6);
                
        // 使用子域名
        $cfgs['domain']['wx'] = 'https://weixin.my.com/';
        $cfgs['domain']['user'] = 'https://user.my.com/';
        $router = new Simple($cfgs);
        
        // user.开头使用 https://user.my.com，后面部分不变
        $exp7 = 'https://user.my.com/demo/user.login.html';
        $url7 = $router->createUrl('user.login', [], 1);
        $this->assertEquals($exp7, $url7);
        
        // wx.开头使用 https://weixin.my.com，后面部分不变
        $exp8 = 'https://weixin.my.com/demo/wx.login.html';
        $url8 = $router->createUrl('wx.login', [], 1);
        $this->assertEquals($exp8, $url8);
         
        // ===================================
        
        $cfgs = [
            'routeClass'       => '\\wf\\route\\Simple',
            'useModule'        => 0,       // 是否启用模块
            
            'defaultMod'       => 'common',   // 默认模块
            'defaultCtl'       => 'default',  // 默认控制器
            'defaultAct'       => 'index',    // 默认action
            
            'hostInfo'         => 'https://www.my.com', // 如：http://www.yoursite.com
            'basePath'         => '/demo/', // 如：/ctx/
            
            'domain'      => [
                'user.main'  => 'https://login.my.com',
                'i'          => 'https://i.my.com',
                'm'          => 'https://m.my.com',
            ],
            
            'staticPath'       => '',
            'rewrite'          => 1,       // 启用URLRewrite
            'rewriteExt'       => '.html', // 链接后缀，如：.html
            'fullUrl'          => 0,       // 是否使用完整URL（http://开头）
            'encode'           => 0,       // 是否对链接参数进行编码
            
            'alias' => [
                'hi' => 'welcome.main.hello',
                'about' => 'article.detail/about/us',
                'login' => 'user.main.login',
            ],
        ];
        
        $routeObj = new Simple($cfgs);
        $uri = $routeObj->createUrl('welcome.main.hello');
        $this->assertEquals($uri, 'hi.html');
        
        $uri = $routeObj->createUrl('welcome.main.hello/yes');
        $this->assertEquals($uri, 'hi/yes.html');

        $uri = $routeObj->createUrl('welcome.main.hello?name=hic&no=12536');
        $this->assertEquals($uri, 'hi.html?name=hic&no=12536');
        
        $uri = $routeObj->createUrl('welcome.main.hello', ['aa' => 'aaaa', 'bb' => 'bbbb']);
        $this->assertEquals($uri, 'hi/aa:aaaa/bb:bbbb.html');
        
        $url = $routeObj->createUrl('welcome.main.hello', [], 1);
        $this->assertEquals($url, 'https://www.my.com/demo/hi.html');
        
        $url = $routeObj->createUrl('welcome.main.hello', ['aa' => 'aaaa', 'bb' => 'bbbb'], 1);
        $this->assertEquals($url, 'https://www.my.com/demo/hi/aa:aaaa/bb:bbbb.html');

        $url = $routeObj->createUrl('welcome.main.hello#yahaa', ['aa' => 'aaaa', 'bb' => 'bbbb'], 1);
        $this->assertEquals($url, 'https://www.my.com/demo/hi/aa:aaaa/bb:bbbb.html#yahaa');
        
        $uri = $routeObj->createUrl('welcome.main.hello', ['lang' => 'zh-CN', 'title' => '中文']);
        $this->assertEquals($uri, 'hi/lang:zh-CN/title:%E4%B8%AD%E6%96%87.html');

        $uri9 = $routeObj->createUrl('user.main.login', [], 1);
        $this->assertEquals('https://login.my.com/demo/login.html', $uri9);
        
        $uri10 = $routeObj->createUrl('i.home?from=yax', [], 1);
        $this->assertEquals('https://i.my.com/demo/i.home.html?from=yax', $uri10);

        $cfgs['useModule'] = 1;
        $routeObj = new Simple($cfgs);
        
        $uri11 = $routeObj->createUrl('m.shop.index', [], 1);
        $this->assertEquals('https://m.my.com/demo/m.shop.index.html', $uri11);

        $routeObj = new Simple(array_replace_recursive($cfgs, [
            'rewrite' => 0,
            'scriptName' => 'app.php',
        ]));
        $uri12 = $routeObj->createUrl('article.detail/about/us', [], 1);
        $exp = 'https://www.my.com/demo/app.php?about.html';
        $this->assertEquals($exp, $uri12);
        
        $cfgs['encode'] = 1;
        $routeObj = new Simple($cfgs);
        $uri = $routeObj->createUrl('welcome.main.hello', ['lang' => 'zh-CN', 'title' => '中文']);
        $this->assertEquals($uri, 'q_aGkvbGFuZzp6aC1DTi90aXRsZTolRTQlQjglQUQlRTYlOTYlODc.html');

    }
}

