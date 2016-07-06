<?php

namespace Joseki\Migration\Console\Command;

use Joseki\Migration\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
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
        $this->setName('joseki:migration:create');
        $this->setDescription('Create an empty migration');

        $this->addArgument('name', InputArgument::OPTIONAL, 'Migration name', 'LeanMapper generated');
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        // run the migrations
        $start = microtime(true);
        $this->manager->create($name);
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>New migration created. Total time: ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }
}
