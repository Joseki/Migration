<?php

namespace Joseki\Migration\Console\Command;

use Joseki\Migration\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{
    /** @var Manager */
    private $manager;



    /**
     * Create constructor.
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }



    protected function configure()
    {
        $this->addOption('--date', '-d', InputOption::VALUE_OPTIONAL, 'The date to migrate to');
        $this->setName('joseki:migration:migrate');
        $this->setDescription('Migrate the database');
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getOption('date');

        // run the migrations
        $start = microtime(true);
        if (null !== $date) {
            $this->manager->migrateToDateTime(new \DateTime($date));
        } else {
            $this->manager->migrate();
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>Migration completed. Total time: ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }
}
