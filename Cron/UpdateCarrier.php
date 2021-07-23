<?php
/**
 * Created by PhpStorm.
 * User: rfq
 * Date: 2020/11/17
 * Time: 09:00
 */

namespace Lovevox\Logistics\Cron;

use Magento\Setup\Module\Di\App\Task\Operation\Area;

class UpdateCarrier
{
    protected $_logger;
    protected $helper;

    /**
     * UpdateCarrier constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $appState
     * @param \Lovevox\Logistics\Helper\Data $helper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $appState,
        \Lovevox\Logistics\Helper\Data $helper
    )
    {
        $this->_logger = $logger;
        $this->helper = $helper;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        return $this->helper->updateCarrier();
    }
}
