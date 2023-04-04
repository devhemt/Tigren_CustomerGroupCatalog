<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Setup\Patch\Data;

use Amasty\Groupcat\Model\Rule\Condition;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateRuleConditions implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        // Update condition and action classes for a rule
        $ruleConditionsClassMap = [
            \Amasty\Groupcat\Model\Rule\Condition\Product\Combine::class =>
                Condition\RuleConditions\Combine::class,
            \Magento\CatalogRule\Model\Rule\Condition\Product::class =>
                Condition\RuleConditions\Product::class
        ];
        $actionConditionsClassMap = [
            \Amasty\Groupcat\Model\Rule\Condition\Customer\Combine::class =>
                Condition\ActionConditions\Combine::class,
            \Amasty\Groupcat\Model\Rule\Condition\Customer::class =>
                Condition\ActionConditions\Customer::class
        ];

        $conditionFields = [
            'conditions_serialized' => $ruleConditionsClassMap,
            'actions_serialized' => $actionConditionsClassMap
        ];
        foreach ($conditionFields as $conditionField => $classMap) {
            foreach ($classMap as $oldClassName => $newClassName) {
                $this->moduleDataSetup->getConnection()->update(
                    $this->moduleDataSetup->getTable('amasty_groupcat_rule'),
                    [$conditionField => new \Zend_Db_Expr('REPLACE(`' . $conditionField . '`, '
                        . '\'' . $this->prepareClassName($oldClassName) . '\', '
                        . '\'' . $this->prepareClassName($newClassName) . '\')')
                    ],
                    '`' . $conditionField . '` LIKE '
                    . '\'%' . $this->prepareClassName($oldClassName, true) . '%\''
                );
            }
        }
    }

    public static function getDependencies(): array
    {
        return [
            MoveOldData::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function prepareClassName(string $className, bool $usedInCondition = false): string
    {
        return str_replace(
            '\\',
            $usedInCondition ? '\\\\\\\\\\\\\\\\' : '\\\\\\\\',
            $className
        );
    }
}
