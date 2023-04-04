<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Request;

class Index extends \Amasty\Groupcat\Controller\Adminhtml\Request
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Get a Quote Requests'));

        return $resultPage;
    }
}
