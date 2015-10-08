<?php

namespace UnitTests\Tables;

use Joseki\LeanMapper\Repository;
use LeanMapperQuery\IQuery;

/**
 * @method Translation get($id)
 * @method Translation findOneBy(IQuery $query)
 * @method Translation[] findAll($limit = null, $offset = null)
 * @method Translation[] findBy(IQuery $query)
 * @method Translation[] findCountBy(IQuery $query)
 */
class TranslationRepository extends Repository
{

}
