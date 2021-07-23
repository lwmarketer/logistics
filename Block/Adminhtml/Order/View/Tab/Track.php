<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Lovevox\Logistics\Block\Adminhtml\Order\View\Tab;

/**
 * Order history tab
 *
 * @api
 * @since 100.0.2
 */
class Track extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Lovevox_Logistics::order/view/tab/track.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    private $adminHelper;

    /**
     * @var \Lovevox\Logistics\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Lovevox\Logistics\Helper\Data $helper,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->adminHelper = $adminHelper;
        $this->helper = $helper;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Compose and get order full history.
     *
     * Consists of the status history comments as well as of invoices, shipments and creditmemos creations
     *
     * @TODO This method requires refactoring. Need to create separate model for comment history handling
     * and avoid generating it dynamically
     *
     * @return array
     */
    public function getFullTrack()
    {
        $order = $this->getOrder();
        /** @var \Lovevox\Logistics\Model\LogisticsHistory $history */
        $history = $this->helper->logisticsHistoryFactory->create();
        $history->load($order->getId(), 'order_id');
        $data = [];
        if ($history) {
            /** @var \Lovevox\Logistics\Model\ResourceModel\Carrier\LogisticsCarrierCollection $carrierCollection */
            $carrierCollection = $this->helper->logisticsCarrierCollectionFactory->create();
            /** @var \Lovevox\Logistics\Model\ResourceModel\Country\LogisticsCountryCollection $countryCollection */
            $countryCollection = $this->helper->logisticsCountryCollectionFactory->create();
            //包裹状态
            $data['track_code'] = $history->getData('status');
            $data['track_status'] = $this->getPackageStatus($history->getData('status'));
            //运单号
            $data['track_number'] = $history->getData('track_number');
            $json_content = json_decode($history->getData('track_content'), true);
            if ($json_content) {
                //最新物流信息
                if (isset($json_content['z0']['a']) &&
                    isset($json_content['z0']['d']) &&
                    isset($json_content['z0']['z'])) {
                    $data['new_track_info'] = trim(str_replace(',,', ',', ($json_content['z0']['d'] . ',' . $json_content['z0']['z'])), ',');
                    $data['new_track_time'] = $json_content['z0']['a'];
                }
                //已送到时展示具体天数
                if ($history->getData('status') == \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_DELIVERED) {
                    $data['track_status'] = $data['track_status'] . '(' . $json_content['f'] . ' days)';
                }

                if ($history->getData('status') != 0) {
                    //发件地
                    if ($json_content['b'] > 0) {
                        $data['origin']['country_code'] = $json_content['b'];
                        $orgin_country = $countryCollection->getItemByColumnValue('code', $json_content['b']);
                        if ($orgin_country) {
                            $data['origin']['country'] = $orgin_country->getData('name');
                        }
                        $data['origin']['carrier_code'] = $json_content['w1'];
                        $orgin_carrier = $carrierCollection->getItemByColumnValue('code', $json_content['w1']);
                        if ($orgin_carrier) {
                            $data['origin']['carrier'] = $orgin_carrier->getData('name');
                        }
                        $data['origin']['content'] = $json_content['z1'];
                    }

                    //目的地
                    if ($json_content['c'] > 0) {
                        $data['destination']['country_code'] = $json_content['c'];
                        $destination_country = $countryCollection->getItemByColumnValue('code', $json_content['c']);
                        if ($destination_country) {
                            $data['destination']['country'] = $destination_country->getData('name');
                        }
                        $data['destination']['carrier_code'] = $json_content['w2'];
                        $destination_carrier = $carrierCollection->getItemByColumnValue('code', $json_content['w2']);
                        if ($destination_carrier) {
                            $data['destination']['carrier'] = $destination_carrier->getData('name');
                        }
                        $data['destination']['content'] = $json_content['z2'];
                    }
                }
            }
        }

        return $data;
    }

    protected function getPackageStatus($code)
    {
        switch ($code) {
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_IN_TRANSIT:
                $name = __('In transit');
                break;
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_EXPIRED:
                $name = __('Expired');
                break;
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_PICK_UP:
                $name = __('Pick up');
                break;
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_UNDELIVERED:
                $name = __('Undelivered');
                break;
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_DELIVERED:
                $name = __('Delivered');
                break;
            case \Lovevox\Logistics\Helper\Data::PACKAGE_STATUS_ALERT:
                $name = __('Alert');
                break;
            default:
                $name = __('Not found');
                break;
        }
        return $name;
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Shipping Info');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Shipping Info');
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('logistics/*/track', ['_current' => true]);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
