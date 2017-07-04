# Windwork 路由

Windwork路由的职责：
- 从url中提取要执行的控制器类、控制器操作、操作的参数及URL其他参数
- 生成符合路由规则的URL。

## 安装
该组件已包含在Windwork框架中，如果你已安装Windwork框架则可以直接使用。

- 安装方式一：通过composer安装（推荐）
```
composer require windwork/wf
```

- 安装方式二：传统方式安装
[下载源码](https://github.com/windwork/wf/releases)后，解压源码到项目文件夹中，然后require_once $PATH_TO_WF/core/lib/Loader.php文件，即可自动加载组件中的类。

## 创建路由实例
```
// 可设置的路由参数
$cfgs = [
    'useModule'   => 0,  // 是否启用模块
    
    'defaultMod'  => 'common',   // 默认模块
    'defaultCtl'  => 'default',  // 默认控制器
    'defaultAct'  => 'index',    // 默认action
    
    'rewrite'     => 0,          // 是否启用URLRewrite
    'rewriteExt'  => '.html',    // URL重写链接后缀，如：.html
    'fullUrl'     => 0,          // 是否使用完整URL（http://开头），一旦设置，所有生成的链接均为完整链接
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
// 创建实例
$router = new \wf\route\strategy\Simple($cfgs);
```

## 1、解析URL
从URL中获取模块、控制器、操作、操作参数，从而可实现动态执行控制器的操作。

不启用模块，所有控制器都放在app/controller文件夹中
```
$uri = 'https://www.my.com/demo/user.auth.login/type:wx/vip:1.html?page=1#axx';
$routeObj->parse($uri);

print_r($routeObj);
```
结果为
```
wf\route\strategy\Simple Object
(
    [ctlClass] => \app\controller\user\AuthController
    [mod] => 
    [ctl] => user.auth
    [act] => login
    [actParams] => Array
        (
        )

    [attributes] => Array
        (
            [type] => wx
            [vip] => 1
        )

    [query] => page=1
    [anchor] => axx
)
```

设置useModule参数为true，启用模块,模块控制器类放在app/{模块名}/controller文件夹中
```
wf\route\strategy\Simple Object
(
    [ctlClass] => \app\user\controller\AuthController
    [mod] => user
    [ctl] => auth
    [act] => login
    [actParams] => Array
        (
        )

    [attributes] => Array
        (
            [type] => wx
            [vip] => 1
        )

    [query] => page=1
    [anchor] => axx
)
```

## 2、生成URL
通过设置路由参数，生成符合路由解析规则的链接。

### 2.1 使用 \wf\route\Simple::createUrl()方法
```
// 生成URL
// 未启用URL重写链接
// index.php?goods.detail/5.html
$router->createUrl('goods.detail/5');


// 未重写完整URL
// https://www.yoursite.com/demo/index.php?goods.detail/5.html
$router->createUrl('goods.detail/5', [], 1);


// 启用URL重写链接
// 'rewrite' => 1

// goods.detail/5.html
$router->createUrl('goods.detail/5');

// 键值对参数 k1:v1/k2:v2
// goods.detail/5/k1:v1/k2:v2.html
$router->createUrl('goods.detail/5/k1:v1/k2:v2');

// 中文键值对参数被url_encode
// goods.detail/5/lang:zh_CN/city:%E5%8C%97%E4%BA%AC.html
$router->createUrl('goods.detail/5', ['lang' => 'zh_CN', 'city' => '北京']);

// 重写完整URL
// https://www.yoursite.com/demo/goods.detail/5.html
$router->createUrl('goods.detail/5', [], 1);


// URL简短化
/*
// 参数设置
'alias' => [
    'reg'    => 'member.account.register',
    'logout' => 'member.auth.logout',
]
*/
// member.account.register.html 被简化成 reg.html
// reg.html
$router->createUrl('member.account.register');

// member.auth.logout.html 被简化成logout.html
// logout.html
$router->createUrl('member.auth.logout');
    
// 使用子域名
/*
// 设置参数
'domain' => [
    'wx' => 'https://weixin.my.com/',
    'user' => 'https://user.my.com/',
] 
*/
// user.开头使用 https://user.my.com，后面部分不变
// https://user.my.com/demo/user.login.html
$router->createUrl('user.login', [], 1);

// wx.开头使用 https://weixin.my.com，后面部分不变
// https://weixin.my.com/demo/wx.login.html
$router->createUrl('wx.login', [], 1);
```
### 2.2 在Windwork框架中使用

为了便于生成URL，我们封装了url()函数。
如果不是独立使用route组件，而是使用Windwork框架开发时，可使用url()函数来生成URL。
```
/**
 * 生成URL
 *
 * @param string $uri
 * @param bool $fullUrl = false 是否获取完整URL
 * @return string
 */
function url($uri, $fullUrl = false) {
    return dsp()->getRouter()->createUrl($uri, [], $fullUrl);
}
```

example:
```
// index.php?user.login
url('user.login')

// index.php?payment.pay.post/type:wx
url('payment.pay.post/type:wx')

// http://yousite/base/index.php?payment.pay.post/type:wx
url('payment.pay.post/type:wx', 1)

// 启用rewrite
// http://yousite/base/payment.pay.post/type:wx
url('payment.pay.post/type:wx', 1)
```

## 3、自定义路由

你可以通过实现 \wf\route\RouteAbstract抽象类约束实现类必须实现的方法及通过定义指定的属性来规范路由参数。
从而让你可以使用官方提供简单路由，也允许你自定义路由，实现按规则解析URL和生成URL。



<br />  
<br />  

### 要了解更多？  
> - [官方完整文档首页](http://docs.windwork.org/manual/)  
> - [官方源码首页](https://github.com/windwork)  
