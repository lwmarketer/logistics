<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lovevox\Logistics\Controller\Adminhtml\Track\AbstractTrack;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

abstract class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Lovevox_Logistics::track';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Shipments grid
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Lovevox_Logistics::sales_logistics')
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Shipping Info'), __('Shipping Info'));
        $resultPage->getConfig()->getTitle()->prepend(__('Shipping Info'));

        return $resultPage;
    }
}
