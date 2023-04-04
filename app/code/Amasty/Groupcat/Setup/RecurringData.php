<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Setup;

use Magento\Framework\DB\AggregatedFieldDataConverterFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RecurringData implements InstallDataInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var AggregatedFieldDataConverterFactory
     */
    private $converterFactory;

    public function __construct(
        MetadataPool $metadataPool,
        ProductMetadataInterface $productMetadata,
        AggregatedFieldDataConverterFactory $converterFactory
    ) {
        $this->metadataPool = $metadataPool;
        $this->productMetadata = $productMetadata;
        $this->converterFactory = $converterFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($setup->tableExists('amasty_groupcat_rule')) {
            $cmsBlockTableName = $setup->getTable('cms_block');
            $this->processRuleForeignKey($setup, $cmsBlockTableName, 'block_id_view');
            $this->processRuleForeignKey($setup, $cmsBlockTableName, 'block_id_list');
        }

        if ($setup->tableExists('amasty_amgroupcat_rules_old')) {
            //drop in recurring data 'cause it takes too much time in patch
            $setup->getConnection()->dropTable($setup->getTable('amasty_amgroupcat_rules_old'));
        }

        $setup->endSetup();
    }

    /**
     * Adding foreign keys outside db_schema setup because
     * cms_block table doesn't have index for block_id column in EE edition.
     * Adding in recurring data because schema always deletes non-schema foreign keys in setup.
     */
    private function processRuleForeignKey(
        ModuleDataSetupInterface $setup,
        string $refTableName,
        string $columnName
    ): void {
        $ruleTableName = $setup->getTable('amasty_groupcat_rule');
        $connection = $setup->getConnection();

        $foreignKeys = $connection->getForeignKeys($ruleTableName);
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey['REF_TABLE_NAME'] === $refTableName
                && $foreignKey['REF_COLUMN_NAME'] === 'block_id'
                && $foreignKey['COLUMN_NAME'] === $columnName
            ) {
                return;
            }
        }
        $connection->addForeignKey(
            $connection->getForeignKeyName(
                $ruleTableName,
                $columnName,
                $refTableName,
                'block_id'
            ),
            $ruleTableName,
            $columnName,
            $refTableName,
            'block_id',
            Table::ACTION_SET_NULL
        );
    }
}
