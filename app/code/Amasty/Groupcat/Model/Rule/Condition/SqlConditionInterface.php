<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition;

use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;

interface SqlConditionInterface
{
    /**
     * Create SQL condition select for entity attribute
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    public function getConditionsSelect($entity, array $params, bool $isFiltered = true): Select;

    /**
     * Get entity identifiers which satisfied condition with parameters
     *
     * @param array $params
     * @return array
     */
    public function getSatisfiedIds(array $params): array;

    /**
     * Check that entity matched with parameters by condition
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($entity, array $params): bool;
}
