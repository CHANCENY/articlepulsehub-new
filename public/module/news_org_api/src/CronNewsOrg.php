<?php

namespace Simp\Public\Module\news_org_api\src;

use Simp\Core\modules\cron\event\CronExecutionResponse;
use Simp\Core\modules\cron\event\CronSubscriber;
use Simp\Core\modules\logger\ErrorLogger;
use Simp\Public\Module\news_org_api\src\Plugin\NewsLiveApi;

class CronNewsOrg implements CronSubscriber
{

    public function run(string $name): CronExecutionResponse
    {
        $response = new CronExecutionResponse();
        $response->name = $name;
        $response->start_timestamp = time();
        $message_line = "";
        try{
           $message_line = NewsLiveApi::cronHandler();
        }catch (\Throwable $e) {
            ErrorLogger::logger()->logError($e);
        }

        $response->message = $message_line;
        $response->status = 200;
        $response->end_timestamp = time();
        $execution_time = $response->end_timestamp - $response->start_timestamp;
        $response->execution_time = $execution_time;
        return $response;
    }
}