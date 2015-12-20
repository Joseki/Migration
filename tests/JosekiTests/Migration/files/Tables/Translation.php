<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\BaseEntity;

/**
 * @property int $id
 * @property Language $lang m:hasOne(lang:)
 * @property Language $language m:hasOne(language:)
 * @property string $translation m:size(155)
 */
class Translation extends BaseEntity
{

}
