<?php

namespace Joseki\Migration\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

class MigrationClassGenerator
{
    protected $name;

    protected $namespace;

    protected $timestamp;

    protected $prefix;

    protected $migrateBody;

    protected $migrateBodyParameters;



    public function __construct($name, $namespace, $timestamp = null, $prefix = '')
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->timestamp = $timestamp ?: time();
        $this->prefix = $prefix ?: 'Migration_';
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

        $class
            ->setFinal(true)
            ->setExtends('Joseki\Migration\DefaultMigration');

        $class->addMethod('beforeMigrate')
            ->addBody('parent::beforeMigrate();');

        $m = $class->addMethod('migrate');

        if ($this->migrateBody) {
            $m->addBody($this->migrateBody, [$this->migrateBodyParameters]);
        }

        $class->addMethod('afterMigrate')
            ->addBody('parent::afterMigrate();');

        return $file;
    }



    public function __toString()
    {
        return (string)$this->generateContent();
    }



    public function saveToDirectory($path)
    {
        file_put_contents(rtrim($path, '/') . '/' . $this->getFullName(false) . '.php', $this->__toString());
    }



    protected function getFullName($fullyQualifiedName = true)
    {
        return ($fullyQualifiedName ? $this->namespace . '\\' : '') . $this->prefix . $this->timestamp . '_' . $this->name;
    }
}