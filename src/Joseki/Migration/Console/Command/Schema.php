<?php

namespace Joseki\Migration\Console\Command;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Joseki\Console\InvalidArgumentException;
use Joseki\Migration\Generator\MigrationClassGenerator;
use Joseki\Migration\Manager;
use Joseki\Migration\MigrationGenerator;
use Joseki\Migration\Generator\LeanMapperSchemaGenerator;
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

    /** @var Container */
    private $container;

    /** @var IMapper */
    private $mapper;

    private $logFile;



    /**
     * @param null|string $logFile
     * @param Manager $manager
     * @param Container $container
     * @param IMapper $mapper
     */
    function __construct($logFile, Manager $manager, Container $container, IMapper $mapper)
    {
        parent::__construct();
        $this->container = $container;
        $this->mapper = $mapper;
        $this->logFile = realpath($logFile);
        $this->manager = $manager;
    }



    protected function configure()
    {
        $this->setName('joseki:migration:from-lm');
        $this->setDescription('Creates database schema from LeanMapper entities');

        $this->addArgument('name', InputArgument::OPTIONAL, 'Migration name', 'LeanMapper generated');
        $this->addOption('print', null, InputOption::VALUE_NONE, 'print sql to input only');
    }



    protected function getEntities()
    {
        $repositoryServices = $this->container->findByType('LeanMapper\Repository');

        $entities = [];
        foreach ($repositoryServices as $service) {
            $repository = $this->container->getService($service);
            $class = get_class($repository);
            $table = $this->mapper->getTableByRepositoryClass($class);
            $entity = $this->mapper->getEntityClass($table);
            $entities[] = new $entity;
        }

        return $entities;
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = $this->getEntities();
        $generator = new LeanMapperSchemaGenerator($this->mapper);
        $platform = new MySqlPlatform;
        $schema = $generator->createSchema($entities);

        if (file_exists($this->logFile)) {
            $fromSchema = unserialize(file_get_contents($this->logFile));
            $sqlStatements = $schema->getMigrateFromSql($fromSchema, $platform);
        } else {
            $sqlStatements = $schema->toSql($platform);
        }

        if (count($sqlStatements) > 0) {
            $output->writeln('Creating database schema...');
            $output->writeln(count($sqlStatements) . ' queries');
            if ($input->getOption('print')) {
                foreach ($sqlStatements as $query) {
                    $output->writeln($query);
                }
            } else {
                file_put_contents($this->logFile, serialize($schema));
                $output->writeln($this->logFile . ' updated');

                $name = $input->getArgument('name');
                $this->manager->createFromLeanMapper($sqlStatements, $name);
            }

        } else {
            $output->writeln('Nothing to change...');
        }
    }
}
