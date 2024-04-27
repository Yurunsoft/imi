<?php

declare(strict_types=1);

namespace Imi\Model\Test\Model;

use Imi\Bean\Annotation\Inherit;
use Imi\Model\Annotation\Column;
use Imi\Model\Test\Model\Base\CreateTimeBase;

/**
 * tb_create_time.
 */
#[Inherit]
class CreateTime extends CreateTimeBase
{
    /**
     * date.
     */
    #[Column(name: 'date', type: 'date', length: 0, default: '')]
    // 测试 CreateTime 注解
    #[\Imi\Model\Annotation\CreateTime()]
    protected ?string $date = null;

    /**
     * time.
     */
    #[Column(name: 'time', type: 'time', length: 0, default: '', createTime: true)]
    protected ?string $time = null;

    /**
     * datetime.
     */
    #[Column(name: 'datetime', type: 'datetime', length: 0, default: '', createTime: true)]
    protected ?string $datetime = null;

    /**
     * timestamp.
     */
    #[Column(name: 'timestamp', type: 'timestamp', length: 0, default: '', createTime: true)]
    protected ?string $timestamp = null;

    /**
     * int.
     */
    #[Column(name: 'int', type: \Imi\Cli\ArgType::INT, length: 11, default: '', createTime: true)]
    protected ?int $int = null;

    /**
     * bigint.
     */
    #[Column(name: 'bigint', type: 'bigint', length: 20, default: '', createTime: true)]
    protected ?int $bigint = null;

    /**
     * year.
     */
    #[Column(name: 'year', type: 'year', length: 4, default: '', createTime: true)]
    protected ?int $year = null;

    /**
     * bigint_second.
     */
    #[Column(name: 'bigint_second', type: 'bigint', length: 20, default: '', createTime: 1)]
    protected ?int $bigintSecond = null;
}
