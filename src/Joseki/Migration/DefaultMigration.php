<?php

namespace Joseki\Migration;

class DefaultMigration extends AbstractMigration
{
    /** @var \DibiConnection */
    private $dibiConnection;



    /**
     * DefaultMigration constructor.
     * @param \DibiConnection $dibiConnection
     */
    public function __construct(\DibiConnection $dibiConnection)
    {
        $this->dibiConnection = $dibiConnection;
    }



    public final function run()
    {
        $this->dibiConnection->begin();
        try {
            parent::run();
            $this->dibiConnection->commit();
        } catch (\Exception $e) {
            $this->dibiConnection->rollback();
        }
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
