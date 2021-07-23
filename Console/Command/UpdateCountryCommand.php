<?php

namespace Lovevox\Logistics\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class TestCommand
 */
class UpdateCountryCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('shell:update-logistics-country')->setDescription('Update Logistics Country Command');
        parent::configure();
    }

    protected $_objectManager;
    protected $updateCountry;
    protected $logger;

    /**
     * UpdateCountryCommand constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Lovevox\Logistics\Cron\UpdateCountry $updateCountry
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Lovevox\Logistics\Cron\UpdateCountry $updateCountry,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->_objectManager = $objectManager;
        $this->updateCountry = $updateCountry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Begin execution");
        $cnt = $this->updateCountry->execute();
        $output->writeln("update total :" . $cnt);
        $output->writeln("End execution");
    }
}
