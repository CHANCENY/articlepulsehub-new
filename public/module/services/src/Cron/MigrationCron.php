<?php

namespace Simp\Public\Module\services\src\Cron;

use Simp\Core\modules\cron\event\CronExecutionResponse;
use Simp\Core\modules\cron\event\CronSubscriber;
use Simp\Core\modules\logger\ErrorLogger;
use Simp\Public\Module\services\src\Cron\MigrateHandle\MigrationHandle;
use Simp\Public\Module\services\src\Cron\MigrateHandle\Resources;

class MigrationCron implements CronSubscriber
{

    /**
     * @throws \Exception
     */
    public function run(string $name): CronExecutionResponse
    {

        $response = new CronExecutionResponse();
        $response->name = $name;
        $response->start_timestamp = time();;
        $messages = [];
        try{
            $resources = new Resources();
            $messages[] = MigrationHandle::migrateCategories($resources);
            $messages[] = MigrationHandle::migrateTags($resources);
            $messages[] = MigrationHandle::migrateBlogs($resources);
        }catch (\Throwable $e) {
            ErrorLogger::logger()->logError($e);
        }


        $message_line = implode("\n",$messages);

        $response->message = $message_line;
        $response->status = 200;
        $response->end_timestamp = time();
        $execution_time = $response->end_timestamp - $response->start_timestamp;
        $response->execution_time = $execution_time;
        return $response;
    }
}