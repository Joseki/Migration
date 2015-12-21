<?php

namespace Joseki\Migration\Database;

use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Database\Adapters\IAdapter;

class Repository
{

    /** @var IAdapter */
    private $adapter;

    /** @var \DibiConnection */
    private $connection;

    /** @var  string */
    private $table;

    private static $defaultAdapter = 'Joseki\Migration\Database\Adapters\MysqlAdapter';

    private $hasSchemaTable;



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
            if (!$this->hasSchemaTable()) {
                $this->getAdapter()->createSchemaTable();
                $this->hasSchemaTable = true;
            }
            $migration->run();
            $this->getAdapter()->log($migration, time());
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        $this->connection->commit();
    }



    public function getCurrentVersion()
    {
        if (!$this->hasSchemaTable()) {
            $this->getAdapter()->createSchemaTable();
            $this->hasSchemaTable = true;
        }
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
                    $class = 'Joseki\Migration\Database\Adapters\MysqlAdapter';
                    break;
                default: // fallback
                    $class = self::$defaultAdapter;
                    break;
            }
            $this->adapter = new $class($this->connection, $this->table);
        }
        return $this->adapter;
    }



    private function hasSchemaTable()
    {
        if ($this->hasSchemaTable === null) {
            $this->hasSchemaTable = $this->getAdapter()->hasSchemaTable();
        }
        return $this->hasSchemaTable;
    }



    public function getExistingVersions()
    {
        if (!$this->hasSchemaTable()) {
            $this->getAdapter()->createSchemaTable();
            $this->hasSchemaTable = true;
        }
        return $this->getAdapter()->getExistingVersions();
    }
}
