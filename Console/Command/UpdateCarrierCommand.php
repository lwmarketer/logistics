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
class UpdateCarrierCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('shell:update-logistics-carrier')->setDescription('Update Logistics Carrier Command');
        parent::configure();
    }

    protected $_objectManager;
    protected $updateCarrier;
    protected $logger;

    /**
     * UpdateCarrierCommand constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Lovevox\Logistics\Cron\UpdateCarrier $updateCarrier
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Lovevox\Logistics\Cron\UpdateCarrier $updateCarrier,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->_objectManager = $objectManager;
        $this->updateCarrier = $updateCarrier;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Begin execution");
        $cnt = $this->updateCarrier->execute();
        $output->writeln("update total :" . $cnt);
        $output->writeln("End execution");
    }
}
