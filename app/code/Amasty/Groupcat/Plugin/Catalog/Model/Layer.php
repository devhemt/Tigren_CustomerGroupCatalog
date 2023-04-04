<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Plugin\Catalog\Model;

use Amasty\Groupcat\Helper\Data;
use Amasty\Groupcat\Model\ProductRuleProvider;
use Magento\Framework\Registry;
use Magento\Search\Model\EngineResolver;

class Layer
{
    public const ELASTICSUITE_SEARCH_ENGINE = 'elasticsuite';

    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var EngineResolver
     */
    private $searchEngineResolver;

    /**
     * @var \Amasty\Groupcat\Model\RestrictedIdsFilter
     */
    private $restrictedIdsFilter;

    public function __construct(
        ProductRuleProvider $ruleProvider,
        Data $helper,
        Registry $coreRegistry,
        EngineResolver $searchEngineResolver,
        \Amasty\Groupcat\Model\RestrictedIdsFilter $restrictedIdsFilter
    ) {
        $this->ruleProvider = $ruleProvider;
        $this->helper = $helper;
        $this->coreRegistry = $coreRegistry;
        $this->searchEngineResolver = $searchEngineResolver;
        $this->restrictedIdsFilter = $restrictedIdsFilter;
    }

    /**
     * Prepare Product Collection for layred Navigation.
     * Add restricted product filter to search engine.
     * In search_request.xml added filter for amasty_groupcat_entity_id
     *
     * @param \Magento\Catalog\Model\Layer                            $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     *
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePrepareProductCollection($subject, $collection)
    {
        if (!$this->helper->isModuleEnabled() || $this->coreRegistry->registry('amasty_ignore_product_filter')) {
            return null;
        }

        $collection->setFlag('groupcat_filter_applied', 1);

        if ($productIds = $this->getRestrictedLayerProductIds()) {
            // add filter to product fulltext search | catalog product collection
            switch ($this->searchEngineResolver->getCurrentSearchEngine()) {
                case EngineResolver::CATALOG_SEARCH_MYSQL_ENGINE:
                    $collection->addFieldToFilter('amasty_groupcat_mysql_entity_id', ['nin' => $productIds]);

                    break;
                case self::ELASTICSUITE_SEARCH_ENGINE:
                    $collection->addFieldToFilter('entity_id', ['neq' => $productIds]);

                    break;
                default:
                    $collection->addFieldToFilter('amasty_groupcat_elastic_entity_id', $productIds);
            }

            return [$collection];
        }

        return null;
    }

    /**
     * @return array
     */
    private function getRestrictedLayerProductIds(): array
    {
        if ($restrictedLayerProductIds = $this->ruleProvider->getRestrictedProductIds()) {
            $currentCategory = $this->coreRegistry->registry('current_category');

            if ($currentCategory) {
                $restrictedLayerProductIds = $this->restrictedIdsFilter->filterProductIdsByCategory(
                    $restrictedLayerProductIds,
                    (int)$currentCategory->getId()
                );
            }

            return $restrictedLayerProductIds;
        }

        return [];
    }
}
