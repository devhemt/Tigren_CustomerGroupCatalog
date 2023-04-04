<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Login implements ObserverInterface
{
    /**
     * @var \Amasty\Groupcat\Model\ProductRuleProvider
     */
    private $ruleResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    public function __construct(
        \Amasty\Groupcat\Model\ResourceModel\Rule $ruleResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->ruleResource = $ruleResource;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->localeDate = $localeDate;
        $this->eventManager = $eventManager;
    }

    public function execute(Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $store = $this->storeManager->getStore();
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $customerId = (int)$this->customerSession->getCustomerId();
        $dateTs = $this->localeDate->scopeTimeStamp($store);
        $restrictedProductIds = $this->ruleResource->getRestrictedProductIds(
            $dateTs,
            $store->getId(),
            $customerGroupId,
            $customerId
        );

        if ($restrictedProductIds) {
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $productId = (int)$quoteItem->getProduct()->getId();
                if (in_array($productId, $restrictedProductIds)) {
                    $this->removeItem($quoteItem);
                }
            }
        }
    }

    /**
     * Remove specific item from guest quote
     *
     * @param $item
     */
    private function removeItem($item)
    {
        $quote = $item->getQuote();

        if ($item->getId()) {
            $quote->removeItem($item->getId());
        } else {
            $item->isDeleted(true);

            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $child->isDeleted(true);
                }
            }

            $parent = $item->getParentItem();

            if ($parent) {
                $parent->isDeleted(true);
            }

            $this->eventManager->dispatch('sales_quote_remove_item', ['quote_item' => $item]);

            //reassemble collection items, otherwise 'deleted' items without ID will be saved
            $collection = $quote->getItemsCollection();
            $items = $collection->getItems();
            $collection->removeAllItems();

            /** @var \Magento\Quote\Model\Quote\Item $row */
            foreach ($items as $row) {
                if (!(!$row->getId() && $row->isDeleted())) {
                    $collection->addItem($row);
                }
            }
        }
    }
}
