<?php

declare(strict_types=1);

namespace Imi\Pgsql\Test\Model\Base;

use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\Entity;
use Imi\Model\Annotation\Table;
use Imi\Pgsql\Model\PgModel as Model;

/**
 * tb_tree 基类.
 *
 * @Entity(camel=true, bean=true, incrUpdate=false)
 *
 * @Table(name="tb_tree", usePrefix=false, id={"id"}, dbPoolName=null)
 *
 * @property int|null    $id
 * @property int|null    $parentId
 * @property string|null $name
 */
abstract class TreeBase extends Model
{
    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEY = 'id';

    /**
     * {@inheritdoc}
     */
    public const PRIMARY_KEYS = ['id'];

    /**
     * id.
     *
     * @Column(name="id", type="int4", length=-1, accuracy=0, nullable=false, default="", isPrimaryKey=true, primaryKeyIndex=0, isAutoIncrement=true, ndims=0, virtual=false)
     */
    protected ?int $id = null;

    /**
     * 获取 id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 赋值 id.
     *
     * @param int|null $id id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->id = null === $id ? null : (int) $id;

        return $this;
    }

    /**
     * parent_id.
     *
     * @Column(name="parent_id", type="int4", length=-1, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, ndims=0, virtual=false)
     */
    protected ?int $parentId = null;

    /**
     * 获取 parentId.
     */
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * 赋值 parentId.
     *
     * @param int|null $parentId parent_id
     *
     * @return static
     */
    public function setParentId($parentId)
    {
        $this->parentId = null === $parentId ? null : (int) $parentId;

        return $this;
    }

    /**
     * name.
     *
     * @Column(name="name", type="varchar", length=32, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false, ndims=0, virtual=false)
     */
    protected ?string $name = null;

    /**
     * 获取 name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 赋值 name.
     *
     * @param string|null $name name
     *
     * @return static
     */
    public function setName($name)
    {
        if (\is_string($name) && mb_strlen($name) > 32)
        {
            throw new \InvalidArgumentException('The maximum length of $name is 32');
        }
        $this->name = null === $name ? null : $name;

        return $this;
    }
}
