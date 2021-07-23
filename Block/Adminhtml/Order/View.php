<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lovevox\Logistics\Block\Adminhtml\Order;

use Magento\Sales\Model\ConfigInterface;

/**
 * Adminhtml sales order view
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class View extends \Magento\Sales\Block\Adminhtml\Order\View
{

    /**
     * Return back url for view grid
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->getRequest()->getParam('customer_id')) {
            return $this->getUrl('customer/index/edit', ['id' => $this->getRequest()->getParam('customer_id')]);
        }
        if ($this->getOrder() && $this->getOrder()->getBackUrl()) {
            return $this->getOrder()->getBackUrl();
        }

        //2021.06.28 荣发强 返回物流信息列表
        if ($this->getRequest()->getParam('com_from') == 'track') {
            return $this->getUrl('logistics/track/');
        }

        return $this->getUrl('sales/*/');
    }

}
