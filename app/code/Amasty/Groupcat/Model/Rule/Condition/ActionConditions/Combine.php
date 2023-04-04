<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\ActionConditions;

use Amasty\Groupcat\Model\Rule\Condition\Address\Billing;
use Amasty\Groupcat\Model\Rule\Condition\Address\BillingFactory;
use Amasty\Groupcat\Model\Rule\Condition\Address\Shipping;
use Amasty\Groupcat\Model\Rule\Condition\Address\ShippingFactory;
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
     * @var CustomerFactory
     */
    private $conditionCustomerFactory;

    /**
     * @var TooltipRendererFactory
     */
    private $tooltipRendererFactory;

    /**
     * @var ShippingFactory
     */
    private $conditionShippingFactory;

    /**
     * @var BillingFactory
     */
    private $conditionBillingFactory;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ConditionApplier $conditionApplier,
        CustomerFactory $conditionFactory,
        ShippingFactory $conditionShippingFactory,
        BillingFactory $conditionBillingFactory,
        TooltipRendererFactory $tooltipRendererFactory,
        array $data = []
    ) {
        parent::__construct($context, $resource, $conditionApplier, $data);
        $this->conditionCustomerFactory = $conditionFactory;
        $this->conditionShippingFactory = $conditionShippingFactory;
        $this->conditionBillingFactory = $conditionBillingFactory;
        $this->tooltipRendererFactory = $tooltipRendererFactory;
        $this->setType(Combine::class);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions(): array
    {
        /** @var Customer $condition */
        $condition = $this->conditionCustomerFactory->create();
        $conditionAttributes = $condition->getNewChildSelectOptions();

        /** @var Billing $conditionBilling */
        $conditionBilling = $this->conditionBillingFactory->create();
        $conditionBillingAttributes = $conditionBilling->getNewChildSelectOptions();

        /** @var Shipping $conditionShipping */
        $conditionShipping = $this->conditionShippingFactory->create();
        $conditionShippingAttributes = $conditionShipping->getNewChildSelectOptions();

        $options = [
            [
                'value' => Combine::class,
                'label' => __('Conditions Combination')
            ],
            [
                'value' => $conditionAttributes,
                'label' => __('Customer attributes')
            ],
            [
                'value' => $conditionBillingAttributes,
                'label' => __('Billing Address')
            ],
            [
                'value' => $conditionShippingAttributes,
                'label' => __('Shipping Address')
            ]
        ];

        return array_merge_recursive(parent::getNewChildSelectOptions(), $options);
    }

    /**
     * Retrieve the list of entity IDs from given params
     *
     * @param array $params target params
     * @param array $excludedIds customer IDs that do not match criteria
     * @return array
     */
    public function getEntityIds(array $params, array $excludedIds): array
    {
        $groupIds = (array)($params['group_id'] ?? null);
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from(
            ['customer' => $this->resource->getTableName('customer_entity')],
            ['entity_id']
        );
        if ($groupIds) {
            $select->where('customer.group_id IN(?)', $groupIds);
        }
        if (!empty($excludedIds)) {
            $select->where('customer.entity_id NOT IN(?)', $excludedIds);
        }

        return $connection->fetchCol($select);
    }

    public function asHtml(): string
    {
        /** @var TooltipRenderer $tooltipRenderer */
        $tooltipRenderer = $this->tooltipRendererFactory->create(
            [
                'tooltipTemplate' => 'Amasty_Groupcat::rule/tooltip/customer.phtml'
            ]
        );

        return parent::asHtml() . $tooltipRenderer->renderTooltip();
    }

    /**
     * Get filter by customer condition for rule matching sql
     *
     * @param DataObject|int|null $entity
     * @param string $fieldName
     * @return string
     */
    protected function createEntityFilter($entity, string $fieldName): string
    {
        $customerId = is_object($entity) ? (int)$entity->getId() : (int)$entity;

        return $customerId
            ? $fieldName . ' = ' . $customerId
            : $fieldName . ' = root.entity_id';
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
        $table = $this->resource->getTableName('customer_entity');
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
