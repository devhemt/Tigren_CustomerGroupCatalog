<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Observer\QuickOrder\Collection;

use Amasty\Groupcat\Model\ConfigProvider;
use Amasty\Groupcat\Model\ProductRuleProvider;
use Amasty\Groupcat\Observer\CatalogCollectionTrait;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * observer for event amasty_quickorder_collection_load_before
 */
class RestrictProductFromQuickOrder implements ObserverInterface
{
    use CatalogCollectionTrait;

    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ProductRuleProvider $ruleProvider,
        ConfigProvider $configProvider
    ) {
        $this->ruleProvider = $ruleProvider;
        $this->configProvider = $configProvider;
    }

    public function execute(Observer $observer): void
    {
        if ($this->configProvider->isEnabled()) {
            $this->restrictCollectionIds(
                $observer->getEvent()->getCollection(),
                $this->ruleProvider->getRestrictedProductIds()
            );
        }
    }
}
