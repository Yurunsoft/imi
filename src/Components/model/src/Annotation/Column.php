<?php

declare(strict_types=1);

namespace Imi\Model\Annotation;

use Imi\Bean\Annotation\Base;

/**
 * 列字段注解.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column extends Base
{
    public function __construct(
        /**
         * 字段名.
         */
        public ?string $name = null,
        /**
         * 字段类型.
         */
        public ?string $type = null,
        /**
         * 字段长度.
         */
        public int $length = -1,
        /**
         * 是否允许为null.
         */
        public bool $nullable = true,
        /**
         * 精度，小数位后几位.
         */
        public int $accuracy = 0,
        /**
         * 默认值
         *
         * @var mixed
         */
        public $default = null,
        /**
         * 是否为主键.
         */
        public bool $isPrimaryKey = false,
        /**
         * 联合主键中的第几个，从0开始.
         */
        public int $primaryKeyIndex = -1,
        /**
         * 是否为自增字段.
         */
        public bool $isAutoIncrement = false,
        /**
         * 虚拟字段，不参与数据库操作.
         */
        public bool $virtual = false,
        /**
         * save/update 模型时是否将当前时间写入该字段；支持 date/time/datetime/timestamp/year/int/bigint；当字段为 int 类型，写入秒级时间戳；当字段为 bigint 类型，写入毫秒级时间戳.
         *
         * @deprecated 3.1
         *
         * @var bool|int
         */
        public $updateTime = false,
        /**
         * 列表分割字符串；如果字段类型为list，并且此字段不为null，读取时会处理为数组，写入时会处理为字符串.
         *
         * @deprecated 3.1
         */
        public ?string $listSeparator = null,
        /**
         * 数组维度，大于0则为数组字段.
         */
        public int $ndims = 0,
        /**
         * 引用字段名，作为引用字段的别名使用，拥有同等的读写能力.
         */
        public string $reference = '',
        /**
         * 字段类型是否为无符号.
         */
        public bool $unsigned = false,
        /**
         * save/create 模型时是否将当前时间写入该字段，save时表有自增ID主键才支持；支持 date/time/datetime/timestamp/year/int/bigint；当字段为 int 类型，写入秒级时间戳；当字段为 bigint 类型，写入毫秒级时间戳.
         *
         * @deprecated 3.1
         *
         * @var bool|int
         */
        public $createTime = false
    ) {
    }
}
