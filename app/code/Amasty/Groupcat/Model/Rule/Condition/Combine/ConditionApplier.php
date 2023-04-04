<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\Combine;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Query\BatchIteratorInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;

class ConditionApplier
{
    private const SQL_BATCH_SIZE = 10000;

    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        QueryGenerator $queryGenerator,
        ResourceConnection $resource
    ) {
        $this->queryGenerator = $queryGenerator;
        $this->resource = $resource;
    }

    /**
     * @param AbstractCombine $condition
     * @param array $params
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSatisfiedIds(AbstractCombine $condition, array $params): array
    {
        $checkAll = $condition->getAggregator() == 'all';
        $allMustBeTrue = $condition->getIsRequired();
        $result = $collectedIds = [];
        $count = 0;

        // Conditions must evaluate to false
        foreach ($condition->getConditions() as $conditionItem) {
            if ($allMustBeTrue) {
                // entity IDs that match child condition
                $entityIds = $conditionItem->getSatisfiedIds($params);
            } else {
                // entity IDs that do not match child condition
                $entityIds = $condition->getEntityIds($params, $conditionItem->getSatisfiedIds($params));
            }

            if ($checkAll) {
                if ($count > 0) {
                    $result = array_intersect($entityIds, $result);
                } else {
                    $result = $entityIds;
                }

                if (empty($result)) {
                    return [];
                }
            } else {
                $collectedIds[] = $entityIds;
            }

            $count++;
        }

        $result = array_merge($result, ...$collectedIds);

        // case when we don't have any condition
        if ($count == 0) {
            $batchSelectIterator = $this->queryGenerator->generate(
                $condition->getIdFieldName(),
                $condition->getConditionsSelect(null, $params, false),
                self::SQL_BATCH_SIZE,
                BatchIteratorInterface::UNIQUE_FIELD_ITERATOR
            );

            $values = [];
            foreach ($batchSelectIterator as $select) {
                $values[] = $this->resource->getConnection()->fetchCol($select);
            }

            $result = array_merge($result, ...$values);
        }

        return array_unique($result);
    }

    /**
     * @param AbstractCombine $condition
     * @param DataObject|int|null $entity
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy(AbstractCombine $condition, $entity, array $params): bool
    {
        $checkAll = $condition->getAggregator() == 'all';
        foreach ($condition->getConditions() as $conditionItem) {
            $result = $conditionItem->isSatisfiedBy($entity, $params);
            if (!$result && $checkAll) {
                return false;
            }

            if ($result && !$checkAll) {
                return true;
            }
        }

        return $checkAll;
    }
}
