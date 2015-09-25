<?php

namespace Joseki\Migration\Database;

use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Database\Adapter\IAdapter;

class Repository
{

    /** @var IAdapter */
    private $adapter;

    /** @var \DibiConnection */
    private $connection;

    /** @var  string */
    private $table;

    private static $defaultAdapter = 'MysqlAdapter';



    /**
     * Repository constructor.
     * @param $table
     * @param \DibiConnection $connection
     */
    public function __construct($table, \DibiConnection $connection)
    {
        $this->connection = $connection;
        $this->table = $table;
    }



    public function migrate(AbstractMigration $migration)
    {
        $this->connection->begin();

        try {
            if (!$this->getAdapter()->hasSchemaTable()) {
                $this->getAdapter()->createSchemaTable();
            }
            $migration->run();
            $this->getAdapter()->log($migration, time());
        } catch (\Exception $e) {
            $this->connection->rollback();
            return;
        }

        $this->connection->commit();
    }



    public function getCurrentVersion()
    {
        return $this->getAdapter()->getCurrentVersion();
    }



    /**
     * @return IAdapter
     */
    public function getAdapter()
    {
        if (!$this->adapter) {
            $driver = ucfirst(strtolower($this->connection->getConfig('driver')));
            switch ($this->connection->getConfig($driver)) {
                case IAdapter::DRIVER_MYSQL:
                    $class = 'MysqlAdapter';
                    break;
                default: // fallback
                    $class = self::$defaultAdapter;
                    break;
            }
            $this->adapter = new $class($this->connection, $this->table);
        }
        return $this->adapter;
    }
}
