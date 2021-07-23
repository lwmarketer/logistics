<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lovevox\Logistics\Ui\Component\Listing\Column\Status;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options for Listing Column Status
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Get options
     * array(array('value' => '<value>', 'label' => '<label>'), ...)
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_NOT_FOUND, 'label' => __('Not found')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_IN_TRANSIT, 'label' => __('In transit')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_EXPIRED, 'label' => __('Expired')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_PICK_UP, 'label' => __('Pick up')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_UNDELIVERED, 'label' => __('Undelivered')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_DELIVERED, 'label' => __('Delivered')],
                ['value' => \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_ALERT, 'label' => __('Alert')]
            ];
        }
        return $this->options;
    }
}
