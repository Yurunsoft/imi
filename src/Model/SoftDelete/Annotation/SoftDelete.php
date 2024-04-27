<?php

declare(strict_types=1);

namespace Imi\Model\SoftDelete\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Config;

/**
 * 软删除.
 *
 * @Annotation
 *
 * @Target("CLASS")
 *
 * @property string $field     软删除字段名
 * @property mixed  $default   软删除字段的默认值，代表非删除状态
 * @property bool   $postWhere 软删除字段查询时是否为后置条件，一般用于索引优化可以设为 true，默认为 false
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SoftDelete extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'field';

    /**
     * @param mixed $default
     */
    public function __construct(?array $__data = null, string $field = '', $default = 0, bool $postWhere = false)
    {
        parent::__construct(...\func_get_args());
        if ('' === $this->field)
        {
            $this->field = Config::get('@app.model.softDelete.fields.deleteTime', 'delete_time');
        }
    }
}
