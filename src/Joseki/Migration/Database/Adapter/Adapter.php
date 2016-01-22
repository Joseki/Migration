<?php

namespace Joseki\Migration\Database\Adapters;

use Dibi\Connection;
use Joseki\Migration\AbstractMigration;
use Joseki\Migration\Database\Repository;

abstract class Adapter implements IAdapter
{
    /** @var \Dibi\Connection */
    protected $connection;

    /** @var Repository */
    private $repository;



    /**
     * Repository constructor.
     * @param \Dibi\Connection $connection
     * @param Repository $repository
     * @internal param $table
     */
    public function __construct(\Dibi\Connection $connection, Repository $repository)
    {
        $this->connection = $connection;
        $this->repository = $repository;
    }



    public function getCurrentVersion()
    {
        $version = $this->connection->select('%n', 'version')->from('%n', $this->getTable())->orderBy('%n DESC', 'version')->fetchSingle();
        return $version ? (int)$version : 0;
    }



    public function log(AbstractMigration $migration, $timestamp)
    {
        $datetime = new \DateTime();
        $this->connection->insert($this->getTable(), ['version' => $migration->getVersion(), 'executed' => $datetime->setTimestamp($timestamp)])->execute();
    }



    public function getTable()
    {
        return $this->repository->getTable();
    }
}
