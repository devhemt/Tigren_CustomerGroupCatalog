<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Plugin\Indexer;

use Amasty\Groupcat\Model\Indexer\Product\ProductRuleProcessor;

class Category extends \Magento\CatalogRule\Plugin\Indexer\Category
{
    /**
     * override constructor. Indexer is changed
     *
     * @param ProductRuleProcessor $productRuleProcessor
     */
    public function __construct(
        ProductRuleProcessor $productRuleProcessor
    ) {
        $this->productRuleProcessor = $productRuleProcessor;
    }
}
