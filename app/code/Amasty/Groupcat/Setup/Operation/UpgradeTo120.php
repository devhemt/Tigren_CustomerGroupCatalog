<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Setup\Operation;

use Amasty\Groupcat\Api\Data\RuleInterface;
use Magento\Framework\DB\AggregatedFieldDataConverterFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldToConvert;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeTo120
{
    /**
     * @var Serialize
     */
    private $serializer;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var AggregatedFieldDataConverterFactory
     */
    private $converterFactory;

    public function __construct(
        Serialize $serializer,
        MetadataPool $metadataPool,
        AggregatedFieldDataConverterFactory $converterFactory
    ) {
        $this->serializer = $serializer;
        $this->metadataPool = $metadataPool;
        $this->converterFactory = $converterFactory;
    }

    public function execute(ModuleDataSetupInterface $setup)
    {
        if (!$setup->tableExists('amasty_amgroupcat_rules_old')) {
            return;
        }
        $setup->startSetup();

        $oldTable = $setup->getTable('amasty_amgroupcat_rules_old');
        $newTable = $setup->getTable('amasty_groupcat_rule');
        $connection = $setup->getConnection();
        /** @codingStandardsIgnoreStart */
        /* this data will be prepared, parsed and inserted to new tables */
        $relationsSelect = $connection->select()->from(
            $oldTable,
            [
                'rule_id',
                'forbidden_cms_page',
                'price_on_product_view',
                'price_on_product_list',
                'customer_group_ids',
                'stores',
                'categories',
                'matched_prod_ids'
            ]
        );
        $ruleRelationsDataSet = $connection->fetchAll($relationsSelect);

        $ruleDataSelect = $connection->select()->from(
            $oldTable,
            [
                'rule_id',
                'name',
                'enabled',
                'prod_cond_serialize',
                'forbidden_action',
                'allow_direct_links',
                'remove_product_links',
                'remove_category_links',
                'hide_price',
                'from_date',
                'to_date',
                'date_range_enabled',
                'customer_group_enabled'
            ]
        );
        $insertSql = $connection->insertFromSelect(
            $ruleDataSelect,
            $newTable,
            [
                'rule_id',
                'name',
                'is_active',
                'conditions_serialized',
                'forbidden_action',
                'allow_direct_links',
                'hide_product',
                'hide_category',
                'price_action',
                'from_date',
                'to_date',
                'date_range_enabled',
                'customer_group_enabled'
            ]
        );
        $connection->query($insertSql);
        /** @codingStandardsIgnoreEnd */

        /* insert relations from old table to new */
        foreach ($ruleRelationsDataSet as $ruleRow) {
            $this->convertForbiddenCmsPage($setup, $ruleRow);
            $this->prepareBlockIdsForForeign($setup, $ruleRow);
            $this->copyOldCustomerGroupToNew($setup, $ruleRow);
            $this->copyOldStoresToNew($setup, $ruleRow);
            $this->copyOldCategoriesToNew($setup, $ruleRow);
        }
        $this->convertSerializedDataToJson($setup);

        $setup->endSetup();
    }

    /**
     * Prepare CMS Block ID for foreign key.
     *
     * @since 1.2 Column names changed from price_on_product_view to block_id_view
     *        and from price_on_product_list to block_id_list
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     */
    private function prepareBlockIdsForForeign(ModuleDataSetupInterface $setup, array $ruleRow)
    {
        $blockIds = [];
        if ($ruleRow['price_on_product_view']) {
            $blockIds[] = (int)$ruleRow['price_on_product_view'];
        }
        if ($ruleRow['price_on_product_list']) {
            $blockIds[] = (int)$ruleRow['price_on_product_list'];
        }
        if (!count($blockIds)) {
            return;
        }
        $connection = $setup->getConnection();
        /** @codingStandardsIgnoreStart */
        $blockSelect = $connection->select()
            ->from($setup->getTable('cms_block'), ['block_id'])
            ->where('block_id IN (?)', $blockIds);

        $blockSet = $connection->fetchAll($blockSelect);
        /** @codingStandardsIgnoreEnd */
        $updateBind = [];

        foreach ($blockSet as $blockRow) {
            if ($blockRow['block_id'] == $ruleRow['price_on_product_view']) {
                $updateBind['block_id_view'] =  $blockRow['block_id'];
            }
            if ($blockRow['block_id'] == $ruleRow['price_on_product_list']) {
                $updateBind['block_id_list'] = $blockRow['block_id'];
            }
        }

        if (count($updateBind) && $ruleRow['rule_id']) {
            $connection->update(
                $setup->getTable('amasty_groupcat_rule'),
                $updateBind,
                $connection->quoteInto('rule_id = ?', $ruleRow['rule_id'])
            );
        }
    }

    /**
     * convert CMS Page identifier to CMS Page ID for foreign key.
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     */
    private function convertForbiddenCmsPage(ModuleDataSetupInterface $setup, array $ruleRow)
    {
        if (!$ruleRow['forbidden_cms_page']) {
            return;
        }
        $connection = $setup->getConnection();
        /** @codingStandardsIgnoreStart */
        $pageSelect = $connection->select()
            ->from($setup->getTable('cms_page'), ['page_id'])
            ->where('identifier = ?', $ruleRow['forbidden_cms_page']);
        $pageId = $connection->fetchOne($pageSelect);
        /** @codingStandardsIgnoreEnd */
        if ($pageId && $ruleRow['rule_id']) {
            $connection->update(
                $setup->getTable('amasty_groupcat_rule'),
                ['forbidden_page_id' => $pageId],
                $connection->quoteInto('rule_id = ?', $ruleRow['rule_id'])
            );
        }
    }

    /**
     * Copy data from old table
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     */
    private function copyOldCustomerGroupToNew(ModuleDataSetupInterface $setup, array $ruleRow)
    {
        if (!is_string($ruleRow['customer_group_ids']) || empty($ruleRow['customer_group_ids'])) {
            return;
        }
        $this->prepareAndInsertOldData(
            $setup,
            $ruleRow,
            $this->serializer->unserialize($ruleRow['customer_group_ids']),
            $setup->getTable('customer_group'),
            $setup->getTable('amasty_groupcat_rule_customer_group'),
            'customer_group_id',
            'customer_group_id'
        );
    }

    /**
     * Copy data from old table
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     */
    private function copyOldStoresToNew(ModuleDataSetupInterface $setup, array $ruleRow)
    {
        $this->prepareAndInsertOldData(
            $setup,
            $ruleRow,
            $ruleRow['stores'],
            $setup->getTable('store'),
            $setup->getTable('amasty_groupcat_rule_store'),
            'store_id',
            'store_id'
        );
    }

    /**
     * Copy data from old table
     *
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     */
    private function copyOldCategoriesToNew(ModuleDataSetupInterface $setup, array $ruleRow)
    {
        $this->prepareAndInsertOldData(
            $setup,
            $ruleRow,
            $ruleRow['categories'],
            $setup->getTable('catalog_category_entity'),
            $setup->getTable('amasty_groupcat_rule_category'),
            'category_id'
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param array                    $ruleRow
     * @param string|array             $idsSet       ids separated by commas
     * @param string                   $checkTableName  To avoid Foreign key error
     * @param string                   $insertTableName Table should have only 2 columns: 'rule_id' and $targetIdName
     * @param string                   $targetIdName
     * @param string                   $entityIdName    ID name of $checkTableName
     */
    private function prepareAndInsertOldData(
        ModuleDataSetupInterface $setup,
        array $ruleRow,
        $idsSet,
        $checkTableName,
        $insertTableName,
        $targetIdName,
        $entityIdName = 'entity_id'
    ) {
        $insertData = [];
        /* old Relation data was stored in one cell separated by commas */
        $idsArray = is_string($idsSet) ? explode(',', trim($idsSet, ',')) : $idsSet;
        if (!is_array($idsArray) || count($idsArray) < 1) {
            return;
        }
        /* get only exist ids. Avoid Foreign key error */
        /** @codingStandardsIgnoreStart */
        $select = $setup->getConnection()->select()
            ->from($checkTableName, [$entityIdName])
            ->where($entityIdName . ' IN (?)', $idsArray);
        $idsArray = $setup->getConnection()->fetchCol($select);
        /** @codingStandardsIgnoreEnd */

        foreach ($idsArray as $entityId) {
            $insertData[] = [$ruleRow['rule_id'], $entityId];
        }
        if (count($insertData)) {
            $setup->getConnection()->insertArray($insertTableName, ['rule_id', $targetIdName], $insertData);
        }
    }

    /**
     * Convert metadata from serialized to JSON format.
     *
     * @param ModuleDataSetupInterface $setup
     *
     * @return void
     */
    public function convertSerializedDataToJson(ModuleDataSetupInterface $setup)
    {
        $metadata = $this->metadataPool->getMetadata(RuleInterface::class);

        /** @var \Magento\Framework\DB\AggregatedFieldDataConverter $aggregatedFieldConverter */
        $aggregatedFieldConverter = $this->converterFactory->create();
        $aggregatedFieldConverter->convert(
            [
                new FieldToConvert(
                    SerializedToJson::class,
                    $setup->getTable('amasty_groupcat_rule'),
                    $metadata->getLinkField(),
                    'conditions_serialized'
                ),
                new FieldToConvert(
                    SerializedToJson::class,
                    $setup->getTable('amasty_groupcat_rule'),
                    $metadata->getLinkField(),
                    'actions_serialized'
                ),
            ],
            $setup->getConnection()
        );
    }
}
