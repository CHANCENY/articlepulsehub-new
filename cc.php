<?php

require_once __DIR__ . '/vendor/autoload.php';

\Simp\Core\lib\app\App::consoleApp();

$news = new \Simp\Public\Module\news_org_api\src\Plugin\NewsLiveApi();

if (!defined('NEWS_LIVE_SOURCES')) {
    throw new Exception('NEWS_LIVE_SOURCES is not defined');
}


\Simp\Public\Module\news_org_api\src\Plugin\NewsLiveApi::cronHandler();
