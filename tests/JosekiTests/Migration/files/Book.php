<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\BaseEntity;

/**
 * @property string $id m:size(25)
 * @property Tag[] $tags m:hasMany(::tag:)
 */
class Book extends BaseEntity
{

}
