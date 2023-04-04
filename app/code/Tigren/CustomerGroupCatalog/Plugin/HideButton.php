<?php

namespace Tigren\CustomerGroupCatalog\Plugin;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Http\Context as AuthContext;

class HideButton
{
    private $authContext;

    public function __construct(AuthContext $authContext)
    {
        $this->authContext = $authContext;
    }

    public function afterIsSaleable(Product $product)
    {
        $isLoggedIn = $this->authContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            return true;
        } else {
            return [];
        }
    }
}
