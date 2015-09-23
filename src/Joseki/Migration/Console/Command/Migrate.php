<?php

namespace Joseki\Migration\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{



    protected function configure()
    {
        parent::configure();
        $this->addOption('--date', '-d', InputOption::VALUE_OPTIONAL, 'The date to migrate to');
        $this->setName('migrate');
        $this->setDescription('Migrate the database');
        // todo set help
    }



    protected function initialize()
    {

    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getOption('date');

        // run the migrations
        $start = microtime(true);
        if (null !== $date) {
            $this->migration->migrateToDateTime(new \DateTime($date));
        } else {
            $this->migration->migrate();
        }
        $end = microtime(true);
        $output->writeln('');
        $output->writeln('<comment>Migration completed. Total time: ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }
}
