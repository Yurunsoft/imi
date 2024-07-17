<?php

declare(strict_types=1);

namespace Imi\SharedMemory\Process;

use Imi\Config;
use Imi\Swoole\Process\Annotation\Process;
use Imi\Swoole\Process\BaseProcess;
use Imi\Util\Imi;
use Yurun\Swoole\SharedMemory\Server;

/**
 * @Process(name="sharedMemory", unique=true)
 */
class SharedMemoryProcess extends BaseProcess
{
    public function run(\Swoole\Process $process): void
    {
        $running = true;
        \Imi\Event\Event::on('IMI.PROCESS.END', static function () use (&$running) {
            $running = false;
        }, ImiPriority::IMI_MAX);
        $socketFile = Config::get('@app.swooleSharedMemory.socketFile');
        if (null === $socketFile)
        {
            $socketFile = Imi::getRuntimePath('imi-shared-memory.sock');
        }
        $storeTypes = Config::get('@app.swooleSharedMemory.storeTypes', [
            \Yurun\Swoole\SharedMemory\Store\KV::class,
            \Yurun\Swoole\SharedMemory\Store\Stack::class,
            \Yurun\Swoole\SharedMemory\Store\Queue::class,
            \Yurun\Swoole\SharedMemory\Store\PriorityQueue::class,
        ]);
        $server = new Server([
            'socketFile'    => $socketFile,
            'storeTypes'    => $storeTypes,
        ]);
        $server->run();
        fwrite(\STDOUT, 'Process [sharedMemory] start' . \PHP_EOL);
        /** @phpstan-ignore-next-line */
        while ($running)
        {
            sleep(1);
        }
    }
}
