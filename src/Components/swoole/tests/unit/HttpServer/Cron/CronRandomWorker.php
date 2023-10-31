<?php

declare(strict_types=1);

namespace Imi\Swoole\Test\HttpServer\Cron;

use Imi\Cron\Annotation\Cron;
use Imi\Cron\Contract\ICronTask;

#[Cron(id: 'CronRandomWorker', second: '3n', type: 'random_worker')]
class CronRandomWorker implements ICronTask
{
    /**
     * {@inheritDoc}
     */
    public function run(string $id, $data): void
    {
        var_dump('random');
    }
}
