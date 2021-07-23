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

namespace Lovevox\Logistics\Setup;

/**
 * Class InstallSchema
 * @package RLTSquare\ProductReviewImages\Setup
 * @author Umar Chaudhry <umarch@rltsquare.com>
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        //国家地区编码信息表
        $table = $installer->getConnection()->newTable(
            $installer->getTable('logistics_base_country')
        )->addColumn(
            'entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT, null, ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true], 'primary id of this table'
        )->addColumn(
            'code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, ['nullable' => false], 'country code'
        )->addColumn(
            'name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => false], 'country name'
        )->addColumn(
            'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'created_at'
        )->addColumn(
            'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE], 'updated_at'
        );
        $installer->getConnection()->createTable($table);

        //运输商编码
        $table = $installer->getConnection()->newTable(
            $installer->getTable('logistics_base_carrier')
        )->addColumn(
            'entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT, null, ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true], 'primary id of this table'
        )->addColumn(
            'code', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, ['nullable' => false], 'carrier code'
        )->addColumn(
            'can_track', \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT, null, ['nullable' => false], 'is can track'
        )->addColumn(
            'country', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 12, ['nullable' => false], 'carrier country'
        )->addColumn(
            'url', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 128, ['nullable' => false], 'carrier site url'
        )->addColumn(
            'name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => false], 'carrier name'
        )->addColumn(
            'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'created_at'
        )->addColumn(
            'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE], 'updated_at'
        );
        $installer->getConnection()->createTable($table);

        //订单物流运输信息
        $table = $installer->getConnection()->newTable(
            $installer->getTable('sales_order_logistics_history')
        )->addColumn(
            'entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT, null, ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true], 'primary id of this table'
        )->addColumn(
            'order_id', \Magento\Framework\DB\Ddl\Table::TYPE_BIGINT, 20, ['nullable' => false], 'order_id'
        )->addColumn(
            'increment_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 32, ['nullable' => false], 'increment_id'
        )->addColumn(
            'track_number', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => false], 'track number'
        )->addColumn(
            'express', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, ['nullable' => true], 'express'
        )->addColumn(
            'status', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 2, ['nullable' => true, 'default' => -1], 'track status'
        )->addColumn(
            'track_content', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '2M', ['nullable' => true], 'track content'
        )->addColumn(
            'update_date', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '32', ['nullable' => true], 'update flag'
        )->addColumn(
            'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'created_at'
        )->addColumn(
            'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE], 'updated_at'
        );
        $installer->getConnection()->createTable($table);
        $installer->getConnection()->addIndex(
            'sales_order_logistics_history',
            $installer->getIdxName(
                'sales_order_logistics_history',
                ['increment_id', 'track_number', 'express'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['increment_id', 'track_number', 'express'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
        );
        $installer->endSetup();
    }
}
