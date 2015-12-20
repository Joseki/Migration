<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\Repository;
use LeanMapperQuery\IQuery;

/**
 * @method Ignore get($id)
 * @method Ignore findOneBy(IQuery $query)
 * @method Ignore[] findAll($limit = null, $offset = null)
 * @method Ignore[] findBy(IQuery $query)
 * @method Ignore[] findCountBy(IQuery $query)
 */
class IgnoreRepository extends Repository
{

}
