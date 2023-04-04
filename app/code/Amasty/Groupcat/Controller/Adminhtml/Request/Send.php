<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Request;

use Amasty\Groupcat\Model\Source\Status;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;

class Send extends \Amasty\Groupcat\Controller\Adminhtml\Request
{
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Amasty\Groupcat\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Amasty\Groupcat\Model\RequestRepository $requestRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Amasty\Groupcat\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $requestRepository, $coreRegistry);
        $this->transportBuilder = $transportBuilder;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('request_id');

        $message = $this->getRequest()->getParam('email_text');
        if (empty(trim($message))) {
            $this->messageManager->addErrorMessage(__('Please enter a Email Text.'));

            return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/edit/*');
        }

        if ($id) {
            try {
                $model = $this->requestRepository->get($id);

                $emailTo = $model->getEmail();
                $sender = $this->helper->getModuleConfig('general/sender');
                $template = $this->helper->getModuleConfig('general/template');
                if ($this->sendEmail($model, $sender, $emailTo, $template, $message)) {
                    $model->setStatus(Status::ANSWERED);
                    $model->setMessageText($message);
                    $this->requestRepository->save($model);
                    $this->messageManager->addSuccessMessage(__('Email Answer was sent.'));
                }
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This request no longer exists.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Please select request id.'));
        }

        return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
    }

    /**
     * @param \Amasty\Groupcat\Model\Request $model
     * @param array|string $sender
     * @param array|string $emailTo
     * @param string $template
     * @param string $message
     * @return bool
     */
    private function sendEmail(\Amasty\Groupcat\Model\Request $model, $sender, $emailTo, $template, $message)
    {
        try {
            $store = $this->storeManager->getStore($model->getStoreId());
            $data =  [
                'website_name'  => $store->getWebsite()->getName(),
                'group_name'    => $store->getGroup()->getName(),
                'store_name'    => $store->getName(),
                'request'       => $model,
                'store'         => $store,
                'message'       => $message,
                'customer_name' => $model->getName()
            ];

            $transport = $this->transportBuilder->setTemplateIdentifier(
                $template
            )->setTemplateOptions(
                ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $store->getId()]
            )->setTemplateVars(
                $data
            )->setFrom(
                $sender
            )->addTo(
                $emailTo,
                $model->getName()
            )->getTransport();

            $transport->sendMessage();
            return true;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return false;
        }
    }
}
