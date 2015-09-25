<?php

namespace Joseki\Migration\Generator;

use Joseki\Migration\Helper;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\Strings;

class MigrationClassGenerator
{
    protected $name;

    protected $timestamp;

    protected $prefix;

    protected $migrateBody;

    protected $migrateBodyParameters;



    public function __construct($name, $prefix, $timestamp = null)
    {
        $this->name = Helper::format($name);
        $this->prefix = $prefix;
        $this->timestamp = $timestamp ?: time();
    }



    public function setMigrateBody($body, $parameters)
    {
        $this->migrateBody = $body;
        $this->migrateBodyParameters = $parameters;
    }



    public function setQueries(array $sql)
    {
        $this->setMigrateBody("\$this->query(?);", [$sql]);
    }



    public function generateContent()
    {
        $file = new PhpFile();

        $class = $file->addClass($this->getFullName());

        $class->setExtends('Joseki\Migration\DefaultMigration');

        $class->addMethod('beforeMigrate')
            ->addBody('parent::beforeMigrate();');

        $m = $class->addMethod('migrate');

        if ($this->migrateBody) {
            $m->addBody($this->migrateBody, [$this->migrateBodyParameters]);
        }

        $class->addMethod('afterMigrate')
            ->addBody('parent::afterMigrate();');

        $class->addMethod('getName');

        return $file;
    }



    public function __toString()
    {
        return (string)$this->generateContent();
    }



    public function saveToDirectory($path)
    {
        $dir = rtrim($path, '/');
        @mkdir($dir, 0755, true);
        $filename = $this->getFullName() . '.php';
        file_put_contents($dir . '/' . $filename, $this->__toString());
    }



    protected function getFullName()
    {
        return implode('_', [$this->prefix, $this->timestamp, $this->name]);
    }
}
