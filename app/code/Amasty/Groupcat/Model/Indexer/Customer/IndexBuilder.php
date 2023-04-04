<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Indexer\Customer;

use Amasty\Groupcat\Model\Indexer\BatchLoader;
use Amasty\Groupcat\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Amasty\Groupcat\Model\Rule;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class IndexBuilder extends \Amasty\Groupcat\Model\Indexer\AbstractIndexBuilder
{
    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var array
     */
    private $indexedCustomerIds = [];

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Psr\Log\LoggerInterface $logger,
        BatchLoader $batchLoader,
        CustomerCollectionFactory $customerCollectionFactory,
        $batchCount = 1000
    ) {
        parent::__construct($ruleCollectionFactory, $resource, $logger, $batchLoader, $batchCount);
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Reindex by id
     *
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     * @api
     */
    public function reindexByCustomerId($customerId)
    {
        $this->reindexByCustomerIds([$customerId]);
    }

    /**
     * Reindex by ids
     *
     * @param array $ids
     * @throws LocalizedException
     * @return void
     * @api
     */
    public function reindexByCustomerIds(array $ids)
    {
        try {
            $this->doReindexByCustomerIds(array_map('intval', $ids));
        } catch (\Exception $e) {
            $this->critical($e);
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * Reindex by customer ids. Template method
     *
     * @param $customerIds
     * @throws LocalizedException
     */
    protected function doReindexByCustomerIds($customerIds)
    {
        if ($customerIds = array_diff($customerIds, array_keys($this->indexedCustomerIds))) {
            $activeRules = $this->getActiveRules();
            $customers = $this->customerCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', ['in' => $customerIds]);
            $this->cleanIndexByCustomerIds($customerIds);

            foreach ($activeRules as $rule) {
                foreach ($customers as $customer) {
                    if (!in_array($rule->getId(), $this->indexedCustomerIds[$customer->getId()] ?? [])) {
                        $this->applyRule($rule, $customer);
                        $this->indexedCustomerIds[$customer->getId()][] = $rule->getId();
                    }
                }
            }

            $this->refreshNotLoggedInIndexData($activeRules);
        }
    }

    /**
     * Reindex by rule ids. Template method
     *
     * @param array $ids
     * @return void
     */
    protected function doReindexByIds($ids)
    {
        $collection = $this->getAllRules()->addFieldToFilter('rule_id', ['in' => $ids]);

        foreach ($this->batchLoader->batchLoad($collection, $this->batchCount) as $rule) {
            $this->updateRuleCustomerData($rule);
        }
    }

    /**
     * Full reindex Template method
     *
     * @return void
     */
    protected function doReindexFull()
    {
        $collection = $this->getAllRules();

        foreach ($this->batchLoader->batchLoad($collection, $this->batchCount) as $rule) {
            $this->updateRuleCustomerData($rule);
        }
    }

    /**
     * @param \Amasty\Groupcat\Model\ResourceModel\Rule\Collection $activeRules
     * @return void
     */
    private function refreshNotLoggedInIndexData(\Amasty\Groupcat\Model\ResourceModel\Rule\Collection $activeRules)
    {
        $indexedData = [];
        $this->connection->delete(
            $this->getTable('amasty_groupcat_rule_customer'),
            ['customer_id = ?' => GroupInterface::NOT_LOGGED_IN_ID]
        );

        /** @var Rule $rule */
        foreach ($activeRules as $rule) {
            if ($this->isRuleAffectCustomerGroup($rule, GroupInterface::NOT_LOGGED_IN_ID)) {
                $indexedData[] = [
                    'rule_id' => $rule->getRuleId(),
                    'customer_id' => GroupInterface::NOT_LOGGED_IN_ID
                ];
            }
        }

        if (!empty($indexedData)) {
            $this->connection->insertOnDuplicate(
                $this->getTable('amasty_groupcat_rule_customer'),
                $indexedData
            );
        }
    }

    /**
     * @param Rule $rule
     * @param int $customerGroupId
     * @return bool
     */
    private function isRuleAffectCustomerGroup(Rule $rule, int $customerGroupId): bool
    {
        $groupEnabled = $rule->getCustomerGroupEnabled();
        return !$groupEnabled
            || ($groupEnabled && in_array($customerGroupId, $rule->getCustomerGroupIds()));
    }

    /**
     * Clean by customer ids
     *
     * @param int[] $customerIds
     * @return void
     */
    private function cleanIndexByCustomerIds($customerIds)
    {
        $this->connection->delete(
            $this->getTable('amasty_groupcat_rule_customer'),
            ['customer_id IN (?)' => $customerIds]
        );
    }

    /**
     * Reindex Rule Data By Customer
     *
     * @param Rule $rule
     * @param $customer
     * @return $this
     */
    protected function applyRule(Rule $rule, $customer)
    {
        if (!$rule->getId() || !$customer->getId() || !$rule->validateCustomer($customer)) {
            return $this;
        }

        if ($this->isRuleAffectCustomerGroup($rule, (int)$customer->getGroupId())) {
            $this->connection->insertOnDuplicate(
                $this->resource->getTableName('amasty_groupcat_rule_customer'),
                [
                    'rule_id' => $rule->getId(),
                    'customer_id' => $customer->getId()
                ]
            );
        }

        return $this;
    }

    /**
     * Collect customer matches for Rule
     *
     * @param Rule $rule
     * @return $this
     */
    protected function updateRuleCustomerData(Rule $rule)
    {
        $this->connection->delete(
            $this->getTable('amasty_groupcat_rule_customer'),
            $this->connection->quoteInto('rule_id = ?', $rule->getId())
        );

        if (!$rule->getIsActive()) {
            return $this;
        }

        \Magento\Framework\Profiler::start('__MATCH_CUSTOMERS__');
        $customerIds = $rule->getMatchingCustomerIds();
        \Magento\Framework\Profiler::stop('__MATCH_CUSTOMERS__');
        $indexedData = [];
        $count = 0;

        if ($this->isRuleAffectCustomerGroup($rule, GroupInterface::NOT_LOGGED_IN_ID)) {
            $indexedData[] = [
                'rule_id' => $rule->getId(),
                'customer_id' => GroupInterface::NOT_LOGGED_IN_ID,
            ];
            $count++;
        }

        foreach ($customerIds as $customerId) {
            $indexedData[] = [
                'rule_id' => $rule->getId(),
                'customer_id' => $customerId,
            ];

            if (++$count === $this->batchCount) {
                $this->connection->insertOnDuplicate(
                    $this->getTable('amasty_groupcat_rule_customer'),
                    $indexedData
                );
                $indexedData = [];
                $count = 0;
            }
        }
        if (!empty($indexedData)) {
            $this->connection->insertOnDuplicate(
                $this->getTable('amasty_groupcat_rule_customer'),
                $indexedData
            );
        }

        return $this;
    }
}
