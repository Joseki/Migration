<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\Repository;
use LeanMapperQuery\IQuery;

/**
 * @method Language get($id)
 * @method Language findOneBy(IQuery $query)
 * @method Language[] findAll($limit = null, $offset = null)
 * @method Language[] findBy(IQuery $query)
 * @method Language[] findCountBy(IQuery $query)
 */
class LanguageRepository extends Repository
{

}
