<?php

declare(strict_types=1);

namespace Imi\Test\Component\Model;

use Imi\Bean\Annotation\Inherit;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\Relation\AutoSave;
use Imi\Model\Annotation\Relation\JoinFromMiddle;
use Imi\Model\Annotation\Relation\JoinToMiddle;
use Imi\Model\Annotation\Relation\ManyToMany;

/**
 * Member.
 *
 * @Inherit
 *
 * @property MemberRoleRelation[]|null $roleRelations
 * @property Role[]|null               $roles
 * @property MemberRoleRelation[]|null $roleRelationsWith
 * @property Role[]|null               $rolesWith
 */
class MemberWithRoles extends Member
{
    /**
     * @ManyToMany(model="Role", middle="MemberRoleRelation", rightMany="roles")
     *
     * @JoinToMiddle(field="id", middleField="member_id")
     *
     * @JoinFromMiddle(middleField="role_id", field="id")
     *
     * @AutoSave
     *
     * @var MemberRoleRelation[]|null
     */
    protected $roleRelations = null;

    /**
     * Get the value of roleRelation.
     *
     * @return MemberRoleRelation[]|null
     */
    public function getRoleRelations()
    {
        return $this->roleRelations;
    }

    /**
     * Set the value of roleRelation.
     *
     * @param MemberRoleRelation[]|null $roleRelations
     *
     * @return self
     */
    public function setRoleRelations($roleRelations)
    {
        $this->roleRelations = $roleRelations;

        return $this;
    }

    /**
     * @Column(virtual=true)
     *
     * @var Role[]|null
     */
    protected $roles;

    /**
     * Get the value of roles.
     *
     * @return Role[]|null
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set the value of roles.
     *
     * @param Role[]|null $roles
     *
     * @return self
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @ManyToMany(model="Role", middle="MemberRoleRelation", rightMany="rolesWith", with=true)
     *
     * @JoinToMiddle(field="id", middleField="member_id")
     *
     * @JoinFromMiddle(middleField="role_id", field="id")
     *
     * @AutoSave
     *
     * @var MemberRoleRelation[]|null
     */
    protected $roleRelationsWith = null;

    /**
     * Get the value of roleRelation.
     *
     * @return MemberRoleRelation[]|null
     */
    public function getRoleRelationsWith()
    {
        return $this->roleRelationsWith;
    }

    /**
     * Set the value of roleRelation.
     *
     * @param MemberRoleRelation[]|null $roleRelationsWith
     *
     * @return self
     */
    public function setRoleRelationsWith($roleRelationsWith)
    {
        $this->roleRelationsWith = $roleRelationsWith;

        return $this;
    }

    /**
     * @Column(virtual=true)
     *
     * @var Role[]|null
     */
    protected $rolesWith;

    /**
     * Get the value of rolesWith.
     *
     * @return Role[]|null
     */
    public function getRolesWith()
    {
        return $this->rolesWith;
    }

    /**
     * Set the value of rolesWith.
     *
     * @param Role[]|null $rolesWith
     *
     * @return self
     */
    public function setRolesWith($rolesWith)
    {
        $this->rolesWith = $rolesWith;

        return $this;
    }
}
