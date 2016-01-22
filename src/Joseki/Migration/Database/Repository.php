<?php

namespace Joseki\Migration\Database;

use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Database\Adapters\Adapter;
use Joseki\Migration\Database\Adapters\IAdapter;

class Repository
{

    /** @var IAdapter */
    private $adapter;

    /** @var \Dibi\Connection */
    private $connection;

    /** @var  string */
    private $table;

    private static $defaultAdapter = 'Joseki\Migration\Database\Adapters\MysqlAdapter';

    private $hasSchemaTable;



    /**
     * Repository constructor.
     * @param $table
     * @param \Dibi\Connection $connection
     */
    public function __construct($table, \Dibi\Connection $connection)
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
            switch ($driver) {
                case IAdapter::DRIVER_MYSQL:
                case IAdapter::DRIVER_MYSQLI:
                    $class = 'Joseki\Migration\Database\Adapters\MysqlAdapter';
                    break;
                case IAdapter::DRIVER_SQLSRV:
                    $class = 'Joseki\Migration\Database\Adapters\SqlsrvAdapter';
                    break;
                default: // fallback
                    $class = self::$defaultAdapter;
                    break;
            }
            $this->adapter = new $class($this->connection, $this);
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



    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
