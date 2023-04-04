<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Observer\Product;

use Amasty\Groupcat\Api\Data\RuleInterface;
use Amasty\Groupcat\Helper\Data;
use Amasty\Groupcat\Model\ProductRuleProvider;
use Amasty\Groupcat\Model\RuleRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Cms\Helper\Page;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer for event controller_action_predispatch_catalog_product_view
 */
class ViewPredispatch implements ObserverInterface
{
    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * @var RuleRepository
     */
    private $ruleRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Page
     */
    private $pageHelper;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        Data $helper,
        ProductRuleProvider $ruleProvider,
        RuleRepository $ruleRepository,
        ProductRepository $productRepository,
        Page $pageHelper
    ) {
        $this->ruleProvider = $ruleProvider;
        $this->ruleRepository = $ruleRepository;
        $this->productRepository = $productRepository;
        $this->pageHelper = $pageHelper;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isModuleEnabled()) {
            return;
        }

        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getEvent()->getRequest();
        $productId = $request->getParam('id');

        try {
            if (!$productId || !$product = $this->productRepository->getById($productId)) {
                return;
            }

            $ruleIndex = $this->ruleProvider->getRuleForProduct($product);

            if (!$ruleIndex
                || !array_key_exists(RuleInterface::RULE_ID, $ruleIndex)
                || !$ruleIndex[RuleInterface::RULE_ID]
            ) {
                return;
            }

            $rule = $this->ruleRepository->get($ruleIndex[RuleInterface::RULE_ID]);

            if ($this->ruleProvider->isProductRestricted($product)) {
                $this->helper->setRedirect($observer->getEvent()->getControllerAction(), $rule);
            }
        } catch (NoSuchEntityException $e) {
            null;
        }
    }
}
