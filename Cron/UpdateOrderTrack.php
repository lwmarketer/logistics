<?php
/**
 * Created by PhpStorm.
 * User: rfq
 * Date: 2020/11/17
 * Time: 09:00
 */

namespace Lovevox\Logistics\Cron;

class UpdateOrderTrack
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
     * @param $order_id
     */
    public function execute($order_id = null)
    {
        return $this->helper->updateOrderTrack($order_id);
    }
}
