<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Request;

class Delete extends \Amasty\Groupcat\Controller\Adminhtml\Request
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Amasty\Groupcat\Model\RequestRepository $requestRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context, $requestRepository, $coreRegistry);
        $this->logger = $logger;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->requestRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('Request was deleted.'));

                return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete item right now. Please review the log and try again.')
                );
                $this->logger->critical($e);

                return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/edit', ['id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('Can\'t find a item to delete.'));

        return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
    }
}
