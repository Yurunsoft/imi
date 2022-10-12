<?php

declare(strict_types=1);

namespace Imi\Test\Component\Model;

use Imi\Bean\Annotation\Inherit;
use Imi\Model\Annotation\Column;
use Imi\Test\Component\Model\Base\UpdateTimeBase;

/**
 * UpdateTime.
 *
 * @Inherit
 */
class UpdateTime extends UpdateTimeBase
{
    /**
     * date.
     *
     * @Column(name="date", type="date", length=0, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?string $date = null;

    /**
     * time.
     *
     * @Column(name="time", type="time", length=0, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?string $time = null;

    /**
     * datetime.
     *
     * @Column(name="datetime", type="datetime", length=0, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?string $datetime = null;

    /**
     * timestamp.
     *
     * @Column(name="timestamp", type="timestamp", length=0, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?string $timestamp = null;

    /**
     * int.
     *
     * @Column(name="int", type="int", length=11, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?int $int = null;

    /**
     * bigint.
     *
     * @Column(name="bigint", type="bigint", length=20, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?int $bigint = null;

    /**
     * year.
     *
     * @Column(name="year", type="year", length=4, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, updateTime=true)
     */
    protected ?int $year = null;

    /**
     * bigint_second.
     *
     * @Column(name="bigint_second", type="bigint", length=20, accuracy=0, nullable=true, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, unsigned=false, virtual=false, updateTime=1)
     */
    protected ?int $bigintSecond = null;
}
