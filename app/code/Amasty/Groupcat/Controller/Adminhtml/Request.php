<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml;

use Magento\Framework\Controller\ResultFactory;

abstract class Request extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Amasty_Groupcat::request';

    public const CURRENT_REQUEST_MODEL = 'amasty_groupcat_request_model';

    /**
     * @var \Amasty\Groupcat\Model\RequestRepository
     */
    protected $requestRepository;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Amasty\Groupcat\Model\RequestRepository $requestRepository,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
        $this->coreRegistry = $coreRegistry;
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Amasty Groupcat'), __('Requests'));

        return $resultPage;
    }
}
