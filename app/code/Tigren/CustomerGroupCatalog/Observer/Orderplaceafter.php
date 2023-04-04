<?php

namespace Tigren\CustomerGroupCatalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class Orderplaceafter implements ObserverInterface
{
    protected $logger;
    protected $_resource;

    public function __construct(ResourceConnection $resource, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->_resource = $resource;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $conn = $this->_resource->getConnection();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $session = $objectManager->get('\Magento\Customer\Model\Session');
            $customerid = $session->getCustomer()->getId();
            $order = $observer->getEvent()->getOrder();
            $orderid = $observer->getEvent()->getOrder()->getIncrementId();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $storeid = $storeManager->getStore()->getId();
            $cusgroupid = $session->getCustomer()->getGroupId();

            $items = $order->getAllVisibleItems();
            $productid = [];
            foreach ($items as $item) {
                $productid[] = $item->getProductId();
            }

            foreach ($productid as $p) {
                $select = $conn->select()
                    ->from(['so' => $this->_resource->getTableName('tigren_customergroupcatalog_rule')])
                    ->join(['soi' => $this->_resource->getTableName('tigren_rule_store')],
                        'so.rule_id = soi.rule_id')
                    ->join(['soii' => $this->_resource->getTableName('tigren_rule_customer_group')],
                        'so.rule_id = soii.rule_id')
                    ->join(['soiii' => $this->_resource->getTableName('tigren_rule_products')],
                        'so.rule_id = soiii.rule_id',)
                    ->where('product_id =' . $p->getId())
                    ->where('store_id = ' . $storeid)
                    ->where('customer_group_id = ' . $cusgroupid);
                $result = $conn->fetchAll($select);

                $max = 0;
                $discount = 0;
                $ruleid = 0;
                foreach ($result as $r) {
                    if ($r['priority'] > $max) {
                        $max = $r['priority'];
                        $discount = $r['discount_amount'];
                        $ruleid = $r['rule_id'];
                    }
                }
                $save = $conn->insert('customer_group_catalog_history', [
                    'order_id' => $orderid,
                    'customer_id' => $customerid,
                    'rule_id' => $ruleid
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
