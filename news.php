<?php

require_once __DIR__ . "/vendor/autoload.php";

\Simp\Core\lib\app\App::consoleApp();;

$messages = \Simp\Public\Module\news_org_api\src\Plugin\NewsLiveApi::cronHandler();

print_r($messages);