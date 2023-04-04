<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Indexer;

abstract class AbstractIndexer extends \Magento\CatalogRule\Model\Indexer\AbstractIndexer
{
    public function __construct(
        \Amasty\Groupcat\Model\Indexer\AbstractIndexBuilder $indexBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->indexBuilder = $indexBuilder;
        $this->_eventManager = $eventManager;
    }
}
