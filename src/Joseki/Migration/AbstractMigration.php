<?php

namespace Joseki\Migration;

abstract class AbstractMigration
{

    protected function beforeMigrate()
    {

    }



    protected function migrate()
    {

    }



    protected function afterMigrate()
    {

    }



    public function run()
    {
        $this->beforeMigrate();
        $this->migrate();
        $this->afterMigrate();
    }
}
