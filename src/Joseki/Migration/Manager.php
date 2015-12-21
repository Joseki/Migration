<?php

namespace Joseki\Migration;

use Joseki\Migration\Database\Repository;
use Joseki\Migration\Generator\MigrationClassGenerator;
use Nette\Object;

/**
 * @method onEvent($message)
 */
class Manager extends Object
{
    public $onEvent = [];

    /** @var string */
    private $migrationDir;

    private $migrationPrefix;

    private $migrations = [];

    /** @var Repository */
    private $repository;



    /**
     * Manager constructor.
     * @param Repository $repository
     * @param $migrationDir
     * @param $migrationPrefix
     */
    public function __construct($migrationDir, $migrationPrefix, Repository $repository)
    {
        $this->migrationDir = $migrationDir;
        $this->migrationPrefix = $migrationPrefix;
        $this->repository = $repository;
    }



    public function add(AbstractMigration $migration)
    {
        $version = $migration->getVersion();
        if (array_key_exists($version, $this->migrations)) {
            throw new InvalidStateException("2 migrations with same version '$version' detected");
        }
        $this->migrations[$version] = $migration;
        ksort($this->migrations);
    }



    public function create($name)
    {
        $generator = new MigrationClassGenerator($name, $this->migrationPrefix);
        $generator->saveToDirectory($this->migrationDir);
    }



    public function createFromLeanMapper($sqlStatements, $name)
    {
        $generator = new MigrationClassGenerator($name, $this->migrationPrefix);
        $generator->setQueries($sqlStatements);
        return $generator->saveToDirectory($this->migrationDir);
    }



    public function migrate()
    {
        $this->validateExistingMigrations();
        $currentVersion = $this->repository->getCurrentVersion();

        ksort($this->migrations);
        $migrations = [];
        foreach ($this->migrations as $version => $migration) {
            if ($currentVersion < $version) {
                $migrations[] = $migration;
            }
        }

        $this->applyMigrations($migrations);
    }



    public function migrateToDateTime(\DateTime $date)
    {
        $this->validateExistingMigrations();
        $currentVersion = $this->repository->getCurrentVersion();
        $time = $date->getTimestamp();

        ksort($this->migrations);
        $migrations = [];
        foreach ($this->migrations as $version => $migration) {
            if ($currentVersion < $version && $version <= $time) {
                $migrations[] = $migration;
            }
        }

        $this->applyMigrations($migrations);
    }



    private function validateExistingMigrations()
    {
        $versions = [];
        /** @var AbstractMigration $migration */
        foreach ($this->migrations as $migration) {
            $versions[] = $migration->getVersion();
        }

        $existingVersions = $this->repository->getExistingVersions();

        sort($existingVersions);
        sort($versions);

        while (count($existingVersions)) {
            $e = array_shift($existingVersions);
            if (count($versions) === 0) {
                $message = sprintf('Migration %s is registered in database but migration file is missing', $e);
                $this->onEvent($message);
                throw new InvalidStateException($message);
            }
            $v = array_shift($versions);
            if ($e != $v) {
                $message = sprintf('Migration %s has been skipped. Rename this migration (change timestamp in its name and filename to later value)', $v);
                $this->onEvent($message);
                throw new InvalidStateException($message);
            }
        }
    }


    /**
     * @param AbstractMigration[] $migrations
     * @throws \Exception
     */
    private function applyMigrations($migrations)
    {
        if (count($migrations) === 0) {
            $this->onEvent('Nothing to migrate');
            return;
        }

        try {
            foreach($migrations as $migration){
                $version = $migration->getVersion();
                $name = $migration->getName();
                $date = (new \DateTime())->setTimestamp($version)->format('Y-m-d H:i:s');
                $this->onEvent("Migrating to $version ($date): $name");
                $this->repository->migrate($migration);
                $this->onEvent("SUCCESS" . PHP_EOL);
            }
        } catch (\Exception $e) {
            $this->onEvent('An error occurred during migration. See log for more info.');
            throw $e;
        }
    }
}
