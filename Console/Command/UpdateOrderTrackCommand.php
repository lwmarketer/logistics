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
class UpdateOrderTrackCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('shell:update-logistics-order-history')
            ->addOption('order_id', null, InputOption::VALUE_OPTIONAL, 'Which order_id')
            ->setDescription('Update Logistics Order History Command');
        parent::configure();
    }

    protected $_objectManager;
    protected $updateOrderTrack;
    protected $logger;

    /**
     * UpdateOrderTrackCommand constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Lovevox\Logistics\Cron\UpdateOrderHistory $updateOrderHistory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Lovevox\Logistics\Cron\UpdateOrderTrack $updateOrderTrack,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->_objectManager = $objectManager;
        $this->updateOrderTrack = $updateOrderTrack;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Begin execution");
        $cnt = $this->updateOrderTrack->execute($input->getOption('order_id'));
        $output->writeln("update total :" . $cnt);
        $output->writeln("End execution");
    }
}
