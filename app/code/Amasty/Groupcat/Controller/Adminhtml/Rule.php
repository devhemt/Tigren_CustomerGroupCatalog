<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml;

use Magento\Framework\Controller\ResultFactory;

abstract class Rule extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Amasty_Groupcat::rule';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Amasty\Groupcat\Api\RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var \Amasty\Groupcat\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * Rule constructor.
     *
     * @param \Magento\Backend\App\Action\Context          $context
     * @param \Magento\Framework\Registry                  $coreRegistry
     * @param \Amasty\Groupcat\Api\RuleRepositoryInterface $ruleRepository
     * @param \Amasty\Groupcat\Model\RuleFactory           $ruleFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Amasty\Groupcat\Api\RuleRepositoryInterface $ruleRepository,
        \Amasty\Groupcat\Model\RuleFactory $ruleFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
        $this->ruleRepository = $ruleRepository;
        $this->ruleFactory    = $ruleFactory;
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Customer Group Catalog Rules'), __('Customer Group Catalog Rules'));

        return $resultPage;
    }
}
