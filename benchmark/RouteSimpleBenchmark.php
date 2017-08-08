<?php

require_once __DIR__ . '/../lib/RouteAbstract.php';
require_once __DIR__ . '/../lib/strategy/Simple.php';
require_once __DIR__ . '/../lib/Exception.php';


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

print 'PHP: ' . PHP_VERSION . "\n";

$execTimes = 10000;
// 1
$startTime = microtime(1);

$uri = 'https://www.my.com/demo/hi/yes/i/do.html#';
for ($i = 0; $i < 10000; $i++) {
    $routeObj = new \wf\route\strategy\Simple($cfgs);
    $routeObj->parse($uri . mt_rand(10000, 99999));
}

$useTime = microtime(1) - $startTime;
print "1. parse  {$execTimes}(times), take: ";
print $useTime . "(s)\n";

// 2
$startTime = microtime(1);
$routeObj = new \wf\route\strategy\Simple($cfgs);
$uri = 'https://www.my.com/demo/hi/yes/i/do.html#';
for ($i = 0; $i < 10000; $i++) {
    $routeObj->parse($uri . mt_rand(10000, 99999));
}

$useTime = microtime(1) - $startTime;
print "2. parse  {$execTimes}(times), take: ";
print $useTime . "(s)\n";

// 3
$startTime = microtime(1);
for ($i = 0; $i < 10000; $i++) {
    $routeObj = new \wf\route\strategy\Simple($cfgs);
    $routeObj->createUrl('a.b.c/r:' . mt_rand(10000, 99999));
}

$useTime = microtime(1) - $startTime;
print "3. create {$execTimes}(times), take: ";
print $useTime . "(s)\n";

// 4
$startTime = microtime(1);
$routeObj = new \wf\route\strategy\Simple($cfgs);
for ($i = 0; $i < 10000; $i++) {
    $routeObj->createUrl('a.b.c/' . mt_rand(10000, 99999));
}

$useTime = microtime(1) - $startTime;
print "4. create {$execTimes}(times), take: ";
print $useTime . "(s)\n";


print "\n5. create {$execTimes}(times), take:";

$startTime = microtime(1);
for ($i = 0; $i < 10000; $i++) {
    simpleUrl('a.b.c/r:' . mt_rand(10000, 99999));
}

$useTime = microtime(1) - $startTime;
print $useTime . "(s)\n";
print "\ndone!\n";

function simpleUrl($uri)
{
    $cfg = getCfg();
    $url = "{$cfg['domain']}{$cfg['basePath']}{$uri}";
}

function getCfg()
{
    return [
        'domain' => 'http://www.xx.com:8888',
        'basePath' => '/test/demo/',
    ];
}
