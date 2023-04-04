<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */
namespace Amasty\Groupcat\Plugin\Block\Catalog\Product\ProductList\Item\AddTo;

class Wishlist
{
    /**
     * @var \Amasty\Groupcat\Model\ProductRuleProvider
     */
    private $ruleProvider;

    public function __construct(
        \Amasty\Groupcat\Model\ProductRuleProvider $ruleProvider
    ) {
        $this->ruleProvider = $ruleProvider;
    }

    /**
     * @param \Magento\Wishlist\Block\Catalog\Product\ProductList\Item\AddTo\Wishlist $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundToHtml($subject, $proceed)
    {
        if ($this->ruleProvider->getProductIsHideWishlist($subject->getProduct())) {
            return '';
        }
        return $proceed();
    }
}
