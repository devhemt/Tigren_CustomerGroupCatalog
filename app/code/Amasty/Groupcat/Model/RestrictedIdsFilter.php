<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model;

use Amasty\Base\Model\MagentoVersion;
use Magento\Catalog\Model\ResourceModel\CategoryProduct;
use Magento\Store\Model\StoreManagerInterface;

class RestrictedIdsFilter
{
    /**
     * @var CategoryProduct
     */
    private $categoryProduct;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryProduct $categoryProduct,
        MagentoVersion $magentoVersion
    ) {
        $this->categoryProduct = $categoryProduct;
        $this->storeManager = $storeManager;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Filters restricted product ids by category
     * @param array $ids
     * @param int $categoryId
     *
     * @return array
     */
    public function filterProductIdsByCategory(array $ids, int $categoryId): array
    {
        if (!$ids) {
            return [];
        }

        try {
            $productCategoryIds = $this->getProductIdsByCategory($categoryId);
        } catch (\Exception $e) {
            return $ids;
        }

        return array_values(array_intersect($productCategoryIds, $ids));
    }

    protected function getProductIdsByCategory(int $categoryId): array
    {
        $productTable = $this->categoryProduct->getMainTable();

        //no index_store tables in Magento <2.2.5
        if (version_compare($this->magentoVersion->get(), '2.2.5', '<')) {
            $select = $this->categoryProduct->getConnection()->select()
                ->from($productTable, 'product_id');
        } else {
            $storeId = $this->storeManager->getStore()->getId();
            $select = $this->categoryProduct->getConnection()->select()
                ->from($productTable . '_index_store' . $storeId, 'product_id');
        }
        $select->columns('product_id')->where('category_id = ?', $categoryId);

        return $this->categoryProduct->getConnection()->fetchCol($select);
    }
}
