<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\Repository;
use LeanMapperQuery\IQuery;

/**
 * @method Date get($id)
 * @method Date findOneBy(IQuery $query)
 * @method Date[] findAll($limit = null, $offset = null)
 * @method Date[] findBy(IQuery $query)
 * @method Date[] findCountBy(IQuery $query)
 */
class DateRepository extends Repository
{

}
