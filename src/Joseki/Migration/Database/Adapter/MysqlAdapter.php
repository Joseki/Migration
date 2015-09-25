<?php

namespace Joseki\Migration\Database\Adapter;

class MysqlAdapter extends Adapter
{

    public function hasSchemaTable()
    {
        $database = $this->connection->getConfig('database');
        $row = $this->connection->select('%s', 'TABLE_NAME')
            ->from('%n.%n', 'INFORMATION_SCHEMA', 'TABLES')
            ->where('%n = %s', 'TABLE_SCHEMA', $database)
            ->where('%n = %s', 'TABLE_NAME', $this->table)
            ->fetch();

        return $row !== false;
    }



    public function createSchemaTable()
    {
        $this->connection->query(['CREATE TABLE %n (%n bigint(14) NOT NULL, %n timestamp NOT NULL)', $this->table, 'version', 'executed']);
    }
}