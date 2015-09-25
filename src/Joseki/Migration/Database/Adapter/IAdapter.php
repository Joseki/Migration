<?php

namespace Joseki\Migration\Database\Adapters;

use Joseki\Migration\AbstractMigration;

interface IAdapter
{
    const DRIVER_MYSQL = 'Mysql';



    public function getCurrentVersion();



    public function hasSchemaTable();



    public function createSchemaTable();



    public function log(AbstractMigration $migration, $timestamp);
}
