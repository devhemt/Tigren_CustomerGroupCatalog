<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Utils;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;

class SqlConditionBuilder
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and field value
     *
     * @param string|\Zend_Db_Expr $field
     * @param string $operator
     * @param mixed $value
     * @param bool $skipSplittingValue
     * @return string
     */
    public function build(
        $field,
        string $operator,
        $value,
        bool $skipSplittingValue = false
    ): string {
        $value = $this->prepareValue($value, $skipSplittingValue);

        /*
         * substitute "equal" operator with "is one of" if compared value is not single
         */
        if ((is_array($value) || $value instanceof \Countable)
            && count($value) != 1
            && in_array($operator, ['==', '!='])
        ) {
            $operator = $operator == '==' ? '()' : '!()';
        }
        $sqlOperator = $this->getSqlOperator($operator);
        $condition = '';

        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $condition = [];
                        foreach ($value as $val) {
                            $condition[] = $this->resource->getConnection()->quoteInto(
                                $field . ' ' . $sqlOperator . ' ?',
                                '%' . $val . '%'
                            );
                        }
                        $condition = implode(' AND ', $condition);
                    }
                } else {
                    $condition = $this->resource->getConnection()->quoteInto(
                        $field . ' ' . $sqlOperator . ' ?',
                        '%' . $value . '%'
                    );
                }
                break;
            case '()':
            case '!()':
                if (!empty($value)) {
                    $condition = $this->resource->getConnection()->quoteInto(
                        $field . ' ' . $sqlOperator . ' (?)',
                        (array)$value
                    );
                }
                break;
            case '[]':
            case '![]':
                if (is_array($value) && !empty($value)) {
                    $conditions = [];
                    foreach ($value as $v) {
                        $conditions[] = $this->resource->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->resource->getConnection()->quote($v)]
                        );
                    }
                    $condition = sprintf(
                        '(%s)%s',
                        join(' OR ', $conditions),
                        $operator == '[]' ? '>0' : '=0'
                    );
                } else {
                    if ($operator == '[]') {
                        $condition = $this->resource->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->resource->getConnection()->quote($value)]
                        );
                    } else {
                        $condition = 'NOT (' . $this->resource->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->resource->getConnection()->quote($value)]
                        ) . ')';
                    }
                }
                break;
            case 'finset':
            case '!finset':
                $condition = $this->prepareFindInSetCondition($field, $operator, $value);
                break;
            case 'between':
                $condition = $field . ' ' . sprintf(
                    $sqlOperator,
                    $this->resource->getConnection()->quote($value['start']),
                    $this->resource->getConnection()->quote($value['end'])
                );
                break;
            case '<=>':
                $condition = sprintf('%s %s', $field, $sqlOperator);
                break;
            default:
                $condition = $this->resource->getConnection()->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }

        return $condition;
    }

    /**
     * @param mixed $value
     * @param bool $skipSplittingValue
     * @return mixed
     */
    private function prepareValue($value, bool $skipSplittingValue)
    {
        if (!is_array($value)) {
            // Workaround to allow value with commas
            if ($skipSplittingValue) {
                $prepareValues = [(string)$value];
            } else {
                $prepareValues = explode(',', $value);
            }
            if (count($prepareValues) <= 1) {
                $value = $prepareValues[0];
            } else {
                $value = [];
                foreach ($prepareValues as $val) {
                    $value[] = trim($val);
                }
            }
        }

        return $value;
    }

    /**
     * Prepare SQL condition for 'finset' pseudo-operator
     *
     * 'finset' pseudo-operator is required to correctly processing multiple select's values
     * in case of '==' or '!=' operators selected for comparison operation.
     *
     * @param string|\Zend_Db_Expr $field
     * @param string $operator
     * @param array|string $value
     * @return string
     */
    private function prepareFindInSetCondition($field, string $operator, $value): string
    {
        $condition = '';
        if (is_array($value)) {
            $conditions = [];
            foreach ($value as $v) {
                $sqlCondition = $this->resource->getConnection()->prepareSqlCondition(
                    $field,
                    ['finset' => $this->resource->getConnection()->quote($v)]
                );
                $sqlCondition .= ($operator == 'finset' ? '>0' : '=0');
                $conditions[] = $sqlCondition;
            }
            if ($operator == 'finset') {
                $condition = join(' AND ', $conditions)
                    . ' AND '
                    . strlen(implode(',', $value)) . '=' . $this->resource->getConnection()
                        ->getLengthSql($field);
            } else {
                $condition = join(' OR ', $conditions)
                    . ' OR '
                    . strlen(implode(',', $value)) . '<>' . $this->resource->getConnection()
                        ->getLengthSql($field);
            }
        }

        return $condition;
    }

    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     *
     * @param string $operator
     * @throws LocalizedException
     * @return string
     */
    private function getSqlOperator(string $operator): string
    {
        switch ($operator) {
            case '==':
                return '=';
            case '!=':
                return '<>';
            case '<=>':
                return 'IS NULL';
            case '{}':
                return 'LIKE';
            case '!{}':
                return 'NOT LIKE';
            case '()':
                return 'IN';
            case '!()':
                return 'NOT IN';
            case '[]':
                return 'FIND_IN_SET(%s, %s)';
            case '![]':
                return 'FIND_IN_SET(%s, %s) IS NULL';
            case 'between':
                return 'BETWEEN %s AND %s';
            case 'finset':
            case '!finset':
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $operator;
            default:
                throw new LocalizedException(__('Unknown operator specified.'));
        }
    }
}
