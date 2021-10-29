<?php

declare(strict_types=1);

namespace Imi\Test\Component\Model\Base;

use Imi\Config\Annotation\ConfigValue;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\DDL;
use Imi\Model\Annotation\Entity;
use Imi\Model\Annotation\Table;
use Imi\Model\Model as Model;

/**
 * tb_article 基类.
 *
 * @Entity
 * @Table(name=@ConfigValue(name="@app.models.Imi\Test\Component\Model\Article.name", default="tb_article"), id={"id"}, dbPoolName=@ConfigValue(name="@app.models.Imi\Test\Component\Model\Article.poolName"))
 * @DDL(sql="CREATE TABLE `tb_article` (   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,   `member_id` int(10) unsigned NOT NULL,   `title` varchar(255) NOT NULL,   `content` mediumtext NOT NULL,   `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,   PRIMARY KEY (`id`) USING BTREE,   KEY `member_id` (`member_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT", decode="")
 *
 * @property int|null    $id
 * @property int|null    $memberId
 * @property string|null $title
 * @property string|null $content
 * @property string|null $time
 */
abstract class ArticleBase extends Model
{
    /**
     * id.
     *
     * @Column(name="id", type="int", length=10, accuracy=0, nullable=false, default="", isPrimaryKey=true, primaryKeyIndex=0, isAutoIncrement=true)
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
     * member_id.
     *
     * @Column(name="member_id", type="int", length=10, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false)
     */
    protected ?int $memberId = null;

    /**
     * 获取 memberId.
     */
    public function getMemberId(): ?int
    {
        return $this->memberId;
    }

    /**
     * 赋值 memberId.
     *
     * @param int|null $memberId member_id
     *
     * @return static
     */
    public function setMemberId($memberId)
    {
        $this->memberId = null === $memberId ? null : (int) $memberId;

        return $this;
    }

    /**
     * title.
     *
     * @Column(name="title", type="varchar", length=255, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false)
     */
    protected ?string $title = null;

    /**
     * 获取 title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * 赋值 title.
     *
     * @param string|null $title title
     *
     * @return static
     */
    public function setTitle($title)
    {
        if (\is_string($title) && mb_strlen($title) > 255)
        {
            throw new \InvalidArgumentException('The maximum length of $title is 255');
        }
        $this->title = null === $title ? null : (string) $title;

        return $this;
    }

    /**
     * content.
     *
     * @Column(name="content", type="mediumtext", length=0, accuracy=0, nullable=false, default="", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false)
     */
    protected ?string $content = null;

    /**
     * 获取 content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * 赋值 content.
     *
     * @param string|null $content content
     *
     * @return static
     */
    public function setContent($content)
    {
        if (\is_string($content) && mb_strlen($content) > 16777215)
        {
            throw new \InvalidArgumentException('The maximum length of $content is 16777215');
        }
        $this->content = null === $content ? null : (string) $content;

        return $this;
    }

    /**
     * time.
     *
     * @Column(name="time", type="timestamp", length=0, accuracy=0, nullable=false, default="CURRENT_TIMESTAMP", isPrimaryKey=false, primaryKeyIndex=-1, isAutoIncrement=false)
     */
    protected ?string $time = null;

    /**
     * 获取 time.
     */
    public function getTime(): ?string
    {
        return $this->time;
    }

    /**
     * 赋值 time.
     *
     * @param string|null $time time
     *
     * @return static
     */
    public function setTime($time)
    {
        $this->time = null === $time ? null : (string) $time;

        return $this;
    }
}
