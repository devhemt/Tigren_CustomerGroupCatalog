<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Rule;

class Delete extends \Amasty\Groupcat\Controller\Adminhtml\Rule
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Amasty\Groupcat\Api\RuleRepositoryInterface $ruleRepository,
        \Amasty\Groupcat\Model\RuleFactory $ruleFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context, $coreRegistry, $ruleRepository, $ruleFactory);
        $this->logger = $logger;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->ruleRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The rule is deleted.'));

                return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete item right now. Please review the log and try again.')
                );
                $this->logger->critical($e);

                return $this->resultRedirectFactory->create()->setPath(
                    'amasty_groupcat/*/edit',
                    ['id' => $id]
                );
            }
        }
        $this->messageManager->addErrorMessage(__('Can\'t find a item to delete.'));
        return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
    }
}
