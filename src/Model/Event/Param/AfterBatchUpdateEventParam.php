<?php

declare(strict_types=1);

namespace Imi\Model\Event\Param;

use Imi\Event\EventParam;

class AfterBatchUpdateEventParam extends EventParam
{
    /**
     * 初始化数据.
     */
    public \Imi\Util\LazyArrayObject $data;

    /**
     * 查询结果.
     */
    public \Imi\Db\Query\Interfaces\IResult $result;
}
