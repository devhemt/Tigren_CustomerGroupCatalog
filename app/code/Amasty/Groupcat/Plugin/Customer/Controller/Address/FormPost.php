<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Plugin\Customer\Controller\Address;

use Amasty\Groupcat\Model\Indexer\Customer\IndexBuilder;
use Magento\Customer\Controller\Address\FormPost as AdderssFormPost;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;

class FormPost
{
    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        IndexBuilder $indexBuilder,
        Session $customerSession,
        Registry $registry
    ) {
        $this->indexBuilder = $indexBuilder;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
    }

    public function afterExecute(
        AdderssFormPost $subject,
        $result
    ) {
        $customerId = (int)$this->customerSession->getCustomerId();

        if ($customerId) {
            $this->registry->register('amasty_groupcat_apply_address', true);
            $this->indexBuilder->reindexByCustomerId($customerId);
        }

        return $result;
    }
}
