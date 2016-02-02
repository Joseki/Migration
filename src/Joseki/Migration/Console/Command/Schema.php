<?php

namespace Joseki\Migration\Console\Command;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Joseki\Migration\Manager;
use Joseki\Migration\Generator\LeanMapperSchemaGenerator;
use Joseki\Utils\FileSystem;
use LeanMapper\IMapper;
use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Schema extends Command
{

    /** @var Manager */
    private $manager;

    /** @var IMapper */
    private $mapper;

    private $logFile;

    /** @var array */
    private $entities;

    /** @var AbstractPlatform */
    private $platform;

    /** @var LeanMapperSchemaGenerator */
    private $schemaGenerator;



    /**
     * @param null|string $logFile
     * @param Manager $manager
     * @param IMapper $mapper
     * @param AbstractPlatform $platform
     * @param LeanMapperSchemaGenerator $schemaGenerator
     */
    function __construct($logFile, Manager $manager, IMapper $mapper, AbstractPlatform $platform, LeanMapperSchemaGenerator $schemaGenerator)
    {
        parent::__construct();
        $this->mapper = $mapper;
        $this->logFile = $logFile;
        $this->manager = $manager;
        $this->platform = $platform;
        $this->schemaGenerator = $schemaGenerator;
    }



    public function addRepository(\Joseki\LeanMapper\Repository $repository)
    {
        $class = get_class($repository);
        $table = $this->mapper->getTableByRepositoryClass($class);
        $entity = $this->mapper->getEntityClass($table);
        $this->entities[] = new $entity;
    }



    protected function configure()
    {
        $this->setName('joseki:migration:from-lm');
        $this->setDescription('Creates database schema from LeanMapper entities');

        $this->addArgument('name', InputArgument::OPTIONAL, 'Migration name', 'LeanMapper generated');
        $this->addOption('print', null, InputOption::VALUE_NONE, 'print sql to input only');
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema = $this->schemaGenerator->createSchema($this->entities);

        if (file_exists($this->logFile)) {
            $fromSchema = unserialize(file_get_contents($this->logFile));
            $sqlStatements = $schema->getMigrateFromSql($fromSchema, $this->platform);
        } else {
            $sqlStatements = $schema->toSql($this->platform);
        }

        if (count($sqlStatements) > 0) {
            $output->writeln('Creating database schema...');
            $output->writeln(count($sqlStatements) . ' queries');
            if ($input->getOption('print')) {
                foreach ($sqlStatements as $query) {
                    $output->writeln($query . ';');
                    $output->writeln('');
                }
            } else {
                file_put_contents($this->logFile, serialize($schema));
                $output->writeln($this->logFile . ' updated');

                $name = $input->getArgument('name');
                $migration = $this->manager->createFromLeanMapper($sqlStatements, $name);
                $output->writeln($migration . ' created');
            }

        } else {
            $output->writeln('Nothing to change...');
        }
    }
}
