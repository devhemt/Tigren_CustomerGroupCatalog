<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Indexer\Customer;

use Amasty\Groupcat\Model\Indexer\AbstractIndexer;

class CustomerRuleIndexer extends AbstractIndexer
{
    /**
     * Override constructor. Indexer is changed
     *
     * @param IndexBuilder $indexBuilder
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct( //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
        \Amasty\Groupcat\Model\Indexer\Customer\IndexBuilder $indexBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($indexBuilder, $eventManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteList($ids)
    {
        $this->indexBuilder->reindexByCustomerIds(array_unique($ids));
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecuteRow($id)
    {
        $this->indexBuilder->reindexByCustomerId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities()
    {
        return [
            \Magento\Framework\App\Cache\Type\Block::CACHE_TAG
        ];
    }
}
