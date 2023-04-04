<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Rule;

use Magento\Framework\Controller\ResultFactory;

class NewAction extends \Amasty\Groupcat\Controller\Adminhtml\Rule
{
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);

        return $result->forward('edit');
    }
}
