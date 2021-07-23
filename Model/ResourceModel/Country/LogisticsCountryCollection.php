<?php
/**
 * NOTICE OF LICENSE
 * You may not sell, distribute, sub-license, rent, lease or lend complete or portion of software to anyone.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @package   RLTSquare_ProductReviewImages
 * @copyright Copyright (c) 2017 RLTSquare (https://www.rltsquare.com)
 * @contacts  support@rltsquare.com
 * @license  See the LICENSE.md file in module root directory
 */

namespace Lovevox\Logistics\Model\ResourceModel\Country;


/**
 * Class Collection
 *
 * @package RLTSquare\ProductReviewImages\Model\ResourceModel\ReviewMedia
 * @author Umar Chaudhry <umarch@rltsquare.com>
 */
class LogisticsCountryCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{


    /**
     * Add store data flag
     *
     * @var bool
     */
    protected $_addStoreDataFlag = false;



    /**
     * Add stores data
     *
     * @return $this
     */
    public function addStoreData()
    {
        $this->_addStoreDataFlag = true;
        return $this;
    }

    /**
     * Add entity filter
     *
     * @param int $entityId
     * @return $this
     */
    public function addEntityFilter($entityId)
    {
        $this->getSelect()->where('entity_id = ?', $entityId);
        return $this;
    }


    /**
     * Add status filter
     *
     * @param string $code
     * @return $this
     */
    public function addCodeFilter($code)
    {
        $this->getSelect()->where('code = ?', $code);
        return $this;
    }

    /**
     * constructor
     *
     */
    protected function _construct()
    {
        $this->_init('Lovevox\Logistics\Model\LogisticsCountry', 'Lovevox\Logistics\Model\ResourceModel\LogisticsCountryEntity');
    }
}
