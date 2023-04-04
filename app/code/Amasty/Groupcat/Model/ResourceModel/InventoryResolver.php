<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\ResourceModel;

use Amasty\Groupcat\Model\Di\Wrapper as StockIndexTableNameResolver;
use Amasty\Groupcat\Model\Di\Wrapper as GetStockIdForCurrentWebsiteWrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

class InventoryResolver
{
    /**
     * @var bool
     */
    private $msiEnabled = null;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        Manager $moduleManager,
        StockIndexTableNameResolver $stockIndexTableNameResolver,
        GetStockIdForCurrentWebsiteWrap $getStockIdForCurrentWebsite,
        ResourceConnection $resource
    ) {
        $this->moduleManager = $moduleManager;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
    }

    public function getOutOfStockProductIds(): array
    {
        if ($this->isMSIEnabled()) {
            $productIds = $this->getProductIdsFromInventoryProducts();
        } else {
            $productIds = $this->getProductIdsFromCatalogInventoryProducts();
        }

        return $productIds;
    }

    private function isMSIEnabled(): bool
    {
        if (null === $this->msiEnabled) {
            $this->msiEnabled = $this->moduleManager->isEnabled('Magento_Inventory');
        }

        return $this->msiEnabled;
    }

    /**
     * Returns a "select" object using "Magento Inventory" module
     */
    private function getSelectInventoryProducts(): Select
    {
        $stockIndexTableName = $this->getStockIndexTableName();

        return $this->connection->select()
            ->from(
                ['stock_index' => $this->resource->getTableName($stockIndexTableName)],
                [
                    'qty' => 'stock_index.quantity',
                    'is_in_stock' => 'stock_index.is_salable'
                ]
            )->joinLeft(
                ['product' => $this->resource->getTableName('catalog_product_entity')],
                'product.sku = stock_index.sku',
                ['product_id' => 'product.entity_id']
            );
    }

    /**
     * Get stock index table by stock id.
     */
    private function getStockIndexTableName(): string
    {
        $stockId = $this->getStockIdForCurrentWebsite->execute();

        return $this->stockIndexTableNameResolver->execute($stockId);
    }

    /**
     * Returns a "select" object using "Magento Catalog Inventory" module
     */
    private function getSelectCatalogInventoryProducts(): Select
    {
        return $this->connection->select()
            ->from(['stock_item' => $this->resource->getTableName('cataloginventory_stock_item')]);
    }

    /**
     * Retrieve a list of IDs for out of stock products using "Magento Inventory" module
     */
    private function getProductIdsFromInventoryProducts(): array
    {
        $select = $this->getSelectInventoryProducts()
            ->reset(Select::COLUMNS)
            ->columns(['product.entity_id'])
            ->where(
                'stock_index.is_salable = ?',
                0
            )->orWhere(
                'stock_index.is_salable IS NULL'
            );

        return $this->connection->fetchCol($select);
    }

    /**
     * Retrieve a list of IDs for out of stock products using "Magento Catalog Inventory" module
     */
    private function getProductIdsFromCatalogInventoryProducts(): array
    {
        $select = $this->getSelectCatalogInventoryProducts()
            ->reset(Select::COLUMNS)
            ->columns(['stock_item.product_id'])
            ->where(
                'stock_item.is_in_stock = ?',
                0
            );

        return $this->connection->fetchCol($select);
    }
}
