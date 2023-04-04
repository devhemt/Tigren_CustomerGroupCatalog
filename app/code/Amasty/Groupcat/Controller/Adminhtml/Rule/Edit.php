<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Rule;

class Edit extends \Amasty\Groupcat\Controller\Adminhtml\Rule
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->ruleRepository->get($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This rule no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*');
            }
        } else {
            /** @var \Magento\CatalogRule\Model\Rule $model */
            $model = $this->ruleFactory->create();
        }

        // set entered data if was error when we do save
        $data = $this->_session->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $this->coreRegistry->register(\Amasty\Groupcat\Controller\RegistryConstants::CURRENT_GROUPCAT_RULE_ID, $model);
        $resultPage = $this->_initAction();

        // set title and breadcrumbs
        $breadcrumb = $id ? __('Edit Customer Group Catalog Rule') : __('New Customer Group Catalog Rule');
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb);
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Customer Group Catalog Rule'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getRuleId() ? $model->getName() : __('New Customer Group Catalog Rule')
        );

        return $resultPage;
    }
}
