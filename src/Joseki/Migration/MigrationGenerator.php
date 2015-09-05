<?php

namespace Joseki\Migration;

use Nette\Utils\Strings;

class MigrationGenerator
{

    private $filename;
    private $name;



    function __construct($name = null)
    {
        if (!$name) {
            $name = 'Joseki_migration';
        }
        $name = Strings::webalize($name);
        $name = str_replace('-', '_', $name);
        $timestamp = new \DateTime();
        $this->filename = $timestamp->format('YmdHis') . '_' . $name;
        $this->name = $this->underscoreToCamel($name) . $timestamp->format('YmdHis');
    }



    public function generate($sqls, $dir)
    {
        $class = new \Nette\PhpGenerator\ClassType($this->name);
        $class->setExtends('Netiso\Joseki\Phinx\DibiMigration');
        $class->addMethod('up')
            ->addBody('$this->query(?);', array($sqls));
        $filename = rtrim($dir, '/\\') . '/' . $this->filename . '.php';

        $content = '<?php' . PHP_EOL . PHP_EOL . (string) $class;
        file_put_contents($filename, $content);
    }



    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * underdash_separated -> camelCase
     * @param  string
     * @return string
     */
    protected function underscoreToCamel($s)
    {
        $s = strtolower($s);
        $s = preg_replace('#[_-](?=[a-z])#', ' ', $s);
        $s = substr(ucwords('x' . $s), 1);
        $s = str_replace(' ', '', $s);
        $s = ucfirst($s);
        return $s;
    }
} 
