<?php

namespace Lovevox\Logistics\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{

    protected $objectManager;

    protected $logger;

    protected $logisticsHistoryCollectionFactory;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollectionFactory $logisticsHistoryCollectionFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->logisticsHistoryCollectionFactory = $logisticsHistoryCollectionFactory;
    }


    public function execute()
    {
        $json_params = $this->getRequest()->getParams();

        try {
            $params = json_decode($json_params, true);

            if ($params['event'] == 'TRACKING_UPDATED') {
                $data = $params['data'];
                /** @var \Lovevox\Logistics\Model\ResourceModel\History\LogisticsHistoryCollection $collection */
                $collection = $this->logisticsHistoryCollectionFactory->create();
                $track_number = $data['number'];
                $track_content = $data['track'];
                /** @var \Lovevox\Logistics\Model\LogisticsHistory $info */
                $info = $collection->getItemByColumnValue('track_number', $track_number);
                if ($info) {
                    $info->setData('status', $data['track']['e']);
                    $info->setData('track_content', json_encode($track_content));
                    $info->setData('update_date', date('Y-m-d'));
                    $info->save();
                }
            }
        } catch (\Exception $exception) {
            $this->logger->info('17track webhook =============>error:' . $exception->getTraceAsString());
        }
    }
}
