<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\BaseEntity;

/**
 * @property int $id
 * @property \DateTime $original
 * @property \DateTime $date m:type(date)
 * @property \DateTime $dateTime m:type(datetime)
 * @property \DateTime $timestamp m:type(timestamp)
 * @property \DateTime $timestamp2 m:type(timestamp:true)
 */
class Date extends BaseEntity
{

}
