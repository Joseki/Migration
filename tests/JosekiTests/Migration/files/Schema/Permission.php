<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\BaseEntity;
use Netiso\Auth\Role;
use Netiso\Navigation\Section;

/**
 * @property int $id
 * @property Section $section m:hasOne
 * @property Role $role m:hasOne
 * @property string $access m:enum(self::ACCESS_*) = self::ACCESS_ALLOW m:size(10)
 */
class Permission extends BaseEntity
{
    const ACCESS_ALLOW = 'allow';
    const ACCESS_DENY = 'deny';
}
