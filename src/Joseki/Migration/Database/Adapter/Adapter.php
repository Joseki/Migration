<?php

namespace Joseki\Migration\Database\Adapters;

use Joseki\Migration\AbstractMigration;

abstract class Adapter implements IAdapter
{
    /** @var \Dibi\Connection */
    protected $connection;

    protected $table;



    /**
     * Repository constructor.
     * @param \Dibi\Connection $connection
     * @param $table
     */
    public function __construct(\Dibi\Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }



    public function getCurrentVersion()
    {
        $version = $this->connection->select('%n', 'version')->from('%n', $this->table)->orderBy('%n DESC', 'version')->fetchSingle();
        return $version ? (int)$version : 0;
    }



    public function log(AbstractMigration $migration, $timestamp)
    {
        $datetime = new \DateTime();
        $this->connection->insert($this->table, ['version' => $migration->getVersion(), 'executed' => $datetime->setTimestamp($timestamp)])->execute();
    }
}
