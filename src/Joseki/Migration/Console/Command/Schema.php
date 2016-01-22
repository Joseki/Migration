<?php

namespace Joseki\Migration\Console\Command;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Joseki\Migration\Database\Adapters\SqlsrvAdapter;
use Joseki\Migration\Database\Repository;
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
    private $options;

    /** @var Repository */
    private $repository;

    private $entities;



    /**
     * @param null|string $logFile
     * @param array $options
     * @param Manager $manager
     * @param Repository $repository
     * @param IMapper $mapper
     */
    function __construct($logFile, array $options = array(), Manager $manager, Repository $repository, IMapper $mapper)
    {
        parent::__construct();
        $this->mapper = $mapper;
        $this->logFile = FileSystem::normalizePath($logFile);
        $this->manager = $manager;
        $this->options = $options;
        $this->repository = $repository;
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
        $entities = $this->entities;
        $generator = new LeanMapperSchemaGenerator($this->mapper);
        $adapter = $this->repository->getAdapter();
        if ($adapter instanceof SqlsrvAdapter) {
            $platform = new SQLServerPlatform();
        } else {
            $platform = new MySqlPlatform;
        }
        $schema = $generator->createSchema($entities, $this->options);

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
                $migration = $this->manager->createFromLeanMapper($sqlStatements, $name);
                $output->writeln($migration . ' created');
            }

        } else {
            $output->writeln('Nothing to change...');
        }
    }
}
