<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Rule;

use Amasty\Groupcat\Api\RuleRepositoryInterface;
use Amasty\Groupcat\Controller\Adminhtml\Rule;
use Amasty\Groupcat\Model\ResourceModel\Rule\Collection;
use Amasty\Groupcat\Model\ResourceModel\Rule\CollectionFactory;
use Amasty\Groupcat\Model\RuleFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends Rule
{
    public const ADMIN_RESOURCE = 'Amasty_Groupcat::edit_delete';

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    private $ruleCollectionFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        LoggerInterface $logger,
        CollectionFactory $ruleCollectionFactory,
        Registry $coreRegistry,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory
    ) {
        parent::__construct($context, $coreRegistry, $ruleRepository, $ruleFactory);
        $this->filter = $filter;
        $this->logger = $logger;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * Mass action execution
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();

        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->ruleCollectionFactory->create());
        $deletedRules = 0;

        try {
            foreach ($collection->getItems() as $rule) {
                $this->ruleRepository->delete($rule);
                $deletedRules++;
            }

            $this->messageManager->addSuccessMessage(
                __('%1 rule(s) has been successfully deleted', $deletedRules)
            );

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error has occurred'));
            $this->logger->critical($e);
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
