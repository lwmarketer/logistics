<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Lovevox\Logistics\Block\Adminhtml;

/**
 * Adminhtml sales shipments block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Track extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_controller = 'adminhtml_track';
        $this->_blockGroup = 'Lovevox_Logistics';
        $this->_headerText = __('Shipping Info');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
