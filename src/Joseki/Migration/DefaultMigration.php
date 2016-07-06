<?php

namespace Joseki\Migration;

use Dibi\Connection;

class DefaultMigration extends AbstractMigration
{
    /** @var Connection */
    private $dibiConnection;



    /**
     * DefaultMigration constructor.
     * @param Connection $dibiConnection
     */
    public function __construct(Connection $dibiConnection)
    {
        $this->dibiConnection = $dibiConnection;
    }



    public final function run()
    {
        $this->dibiConnection->begin();
        try {
            parent::run();
        } catch (\Exception $e) {
            $this->dibiConnection->rollback();
            throw $e;
        }
        $this->dibiConnection->commit();
    }



    protected function query($sql)
    {
        if (!is_array($sql)) {
            $sql = [$sql];
        }
        foreach ($sql as $query) {
            $this->dibiConnection->nativeQuery($query);
        }
    }
}
