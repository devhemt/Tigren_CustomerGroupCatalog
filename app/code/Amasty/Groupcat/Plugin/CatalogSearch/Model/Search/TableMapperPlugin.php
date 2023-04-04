<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Plugin\CatalogSearch\Model\Search;

class TableMapperPlugin
{
    /**
     * Fix for Magento < 2.1.6. Method "GetMappingAlias" in not used any more
     *
     * @param \Magento\CatalogSearch\Model\Search\TableMapper   $subject
     * @param callable                                          $proceed
     * @param \Magento\Framework\Search\Request\FilterInterface $filter
     *
     * @return string
     */
    public function aroundGetMappingAlias(
        \Magento\CatalogSearch\Model\Search\TableMapper $subject,
        callable $proceed,
        \Magento\Framework\Search\Request\FilterInterface $filter
    ) {
        if ($filter->getName() == 'entity_filter') {
            return 'search_index';
        }

        return $proceed($filter);
    }
}
