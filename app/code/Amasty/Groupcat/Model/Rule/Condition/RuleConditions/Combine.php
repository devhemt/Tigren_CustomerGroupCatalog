<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\RuleConditions;

use Amasty\Groupcat\Model\Rule\Condition\Combine\AbstractCombine;
use Amasty\Groupcat\Model\Rule\Condition\Combine\ConditionApplier;
use Amasty\Groupcat\Model\Rule\Condition\TooltipRendererFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Context;

class Combine extends AbstractCombine
{
    /**
     * @var ProductFactory
     */
    private $conditionProductFactory;

    /**
     * @var TooltipRendererFactory
     */
    private $tooltipRendererFactory;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ConditionApplier $conditionApplier,
        ProductFactory $conditionFactory,
        TooltipRendererFactory $tooltipRendererFactory,
        array $data = []
    ) {
        parent::__construct($context, $resource, $conditionApplier, $data);
        $this->conditionProductFactory = $conditionFactory;
        $this->tooltipRendererFactory = $tooltipRendererFactory;
        $this->setType(\Amasty\Groupcat\Model\Rule\Condition\RuleConditions\Combine::class);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions(): array
    {
        /** @var \Amasty\Groupcat\Model\Rule\Condition\RuleConditions\Product $condition */
        $condition = $this->conditionProductFactory->create();
        $conditionAttributes = $condition->getNewChildSelectOptions();

        $options = [
            [
                'value' => \Amasty\Groupcat\Model\Rule\Condition\RuleConditions\Combine::class,
                'label' => __('Conditions Combination')
            ],
            [
                'value' => $conditionAttributes,
                'label' => __('Product attributes')
            ]
        ];

        return array_merge_recursive(parent::getNewChildSelectOptions(), $options);
    }

    /**
     * Retrieve the list of product IDs from given params
     *
     * @param array $params target params
     * @param array $excludedIds product IDs that do not match criteria
     * @return array
     */
    public function getEntityIds(array $params, array $excludedIds): array
    {
        $websiteIds = (array)($params['website_id'] ?? null);
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from(
            ['product' => $this->resource->getTableName('catalog_product_entity')],
            ['entity_id']
        );
        if ($websiteIds) {
            $select->joinLeft(
                ['product_website' => $this->resource->getTableName('catalog_product_website')],
                'product_website.product_id = product.entity_id',
                []
            );
            $select->where('product_website.website_id IN(?)', $websiteIds);
        }
        if (!empty($excludedIds)) {
            $select->where('product.entity_id NOT IN(?)', $excludedIds);
        }

        return $connection->fetchCol($select);
    }

    public function asHtml(): string
    {
        /** @var TooltipRenderer $tooltipRenderer */
        $tooltipRenderer = $this->tooltipRendererFactory->create(
            [
                'tooltipTemplate' => 'Amasty_Groupcat::rule/tooltip/product.phtml'
            ]
        );

        return parent::asHtml() . $tooltipRenderer->renderTooltip();
    }

    /**
     * Get filter by product condition for rule matching sql
     *
     * @param DataObject|int|null $entity
     * @param string $fieldName
     * @return string
     */
    protected function createEntityFilter($entity, string $fieldName): string
    {
        $productId = is_object($entity) ? (int)$entity->getId() : (int)$entity;

        return $productId
            ? $fieldName . ' = ' . $productId
            : $fieldName . ' = main.entity_id';
    }

    /**
     * Prepare base condition select which related with current condition combine
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    protected function prepareConditionsSql($entity, array $params, bool $isFiltered = true): Select
    {
        $select = $this->resource->getConnection()->select();
        $table = $this->resource->getTableName('catalog_product_entity');
        if ($isFiltered) {
            $select->from($table, [new \Zend_Db_Expr(1)]);
            $select->where($this->createEntityFilter($entity, 'entity_id'));
        } else {
            $select->from($table, ['entity_id']);
        }

        if ($isFiltered) {
            $select->limit(1);
        }

        return $select;
    }
}
