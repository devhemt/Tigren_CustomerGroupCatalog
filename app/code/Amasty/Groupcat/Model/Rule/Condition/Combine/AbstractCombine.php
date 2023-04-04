<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\Combine;

use Amasty\Groupcat\Model\Rule\Condition\SqlConditionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Context;

abstract class AbstractCombine extends Combine
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ConditionApplier
     */
    protected $conditionApplier;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ConditionApplier $conditionApplier,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->conditionApplier = $conditionApplier;
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType(): array
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Add operator when loading array
     *
     * @param array $arr
     * @param string $key
     * @return $this
     */
    public function loadArray($arr, $key = 'conditions')
    {
        if (isset($arr['operator'])) {
            $this->setOperator($arr['operator']);
        }

        if (isset($arr['attribute'])) {
            $this->setAttribute($arr['attribute']);
        }

        return parent::loadArray($arr, $key);
    }

    /**
     * Get information if condition is required
     *
     * @return bool
     */
    public function getIsRequired(): bool
    {
        return $this->getValue() == 1;
    }

    /**
     * Get conditions, if current prefix is undefined use 'conditions' key
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->validateConditionObjects((array)parent::getConditions());
    }

    /**
     * Get SQL select for matching entity to rule condition
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param boolean $isFiltered
     * @return Select
     */
    public function getConditionsSelect($entity, array $params, bool $isFiltered = true): Select
    {
        /**
         * Build base SQL
         */
        $select = $this->prepareConditionsSql($entity, $params, $isFiltered);
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $operator = $this->getIsRequired() ? '=' : '<>';
        $conditions = [];

        /**
         * Add children subselects conditions
         */
        $connection = $this->resource->getConnection();
        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSelect($entity, $params, $isFiltered)) {
                $isnull = $connection->getCheckSql($sql, 1, 0);
                $conditions[] = '(' . $isnull . ' ' . $operator . ' 1)';
            }
        }

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        return $select;
    }

    /**
     * Check that entity matched with parameters by condition
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($entity, array $params): bool
    {
        return $this->conditionApplier->isSatisfiedBy($this, $entity, $params);
    }

    /**
     * Get entities identifiers which satisfied condition
     *
     * @param array $params
     * @return array
     */
    public function getSatisfiedIds(array $params): array
    {
        return $this->conditionApplier->getSatisfiedIds($this, $params);
    }

    public function getIdFieldName(): string
    {
        return 'entity_id';
    }

    /**
     * Retrieve the list of entity IDs from given params
     *
     * @param array $params target params
     * @param array $excludedIds customer IDs that do not match criteria
     * @return array
     */
    abstract public function getEntityIds(array $params, array $excludedIds): array;

    /**
     * Get filter by entity condition for rule matching sql
     *
     * @param DataObject|int|null $entity
     * @param string $fieldName
     * @return string
     */
    abstract protected function createEntityFilter($entity, string $fieldName): string;

    /**
     * Prepare base condition select which related with current condition combine
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    abstract protected function prepareConditionsSql($entity, array $params, bool $isFiltered = true): Select;

    private function validateConditionObjects(array $conditions): array
    {
        foreach ($conditions as $conditionItem) {
            if (!($conditionItem instanceof Combine)
                && !($conditionItem instanceof SqlConditionInterface)
            ) {
                throw new \RuntimeException(
                    'The condition instance must implement ' . SqlConditionInterface::class
                );
            }
        }

        return $conditions;
    }
}
