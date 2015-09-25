<?php

namespace Joseki\Migration;

use Nette\Utils\Strings;

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



    public final function getVersion()
    {
        if (($version = Strings::match(get_class($this), '#\d{10}#')) === null) {
            throw new InvalidStateException('Invalid migration class name - it does not contains timestamp');
        }
        return (int)$version[0];
    }



    public function getName()
    {
        $class = '_' . get_class($this);
        return substr($class,  strrpos($class, '_') + 1);
    }

}
