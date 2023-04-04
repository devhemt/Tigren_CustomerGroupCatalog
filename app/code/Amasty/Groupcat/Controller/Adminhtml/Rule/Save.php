<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Controller\Adminhtml\Rule;

use Amasty\Groupcat\Api\Data\RuleInterface;

class Save extends \Amasty\Groupcat\Controller\Adminhtml\Rule
{
    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Amasty\Groupcat\Api\RuleRepositoryInterface $ruleRepository,
        \Amasty\Groupcat\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context, $coreRegistry, $ruleRepository, $ruleFactory);
        $this->dataPersistor = $dataPersistor;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        if ($data = $this->getRequest()->getPostValue()) {
            try {
                /** @var \Amasty\Groupcat\Model\Rule $model */
                $model = $this->ruleFactory->create();

                if ($id = $this->getRequest()->getParam(RuleInterface::RULE_ID)) {
                    $model = $this->ruleRepository->get($id);
                }

                $data = $this->prepareRuleDataForSave($data);
                $ruleDataObject = $this->dataObjectFactory->create(['data' => $data]);
                $validateResult = $model->validateData($ruleDataObject);

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $this->messageManager->addErrorMessage($errorMessage);
                    }

                    $this->_getSession()->setPageData($data);
                    $this->dataPersistor->set('amasty_groupcat_rule', $data);

                    return $this->resultRedirectFactory->create()->setPath(
                        'amasty_groupcat/*/edit',
                        ['id' => $model->getId()]
                    );
                }

                $model->loadPost($data);
                $this->_getSession()->setPageData($data);
                $this->dataPersistor->set('amasty_groupcat_rule', $data);
                $this->ruleRepository->save($model);
                $this->messageManager->addSuccessMessage(__('The rule is saved.'));
                $this->_getSession()->setPageData(false);
                $this->dataPersistor->clear('amasty_groupcat_rule');

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        'amasty_groupcat/*/edit',
                        ['id' => $model->getId()]
                    );
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->getRequest()->getParam('id')
                    ? $this->resultRedirectFactory->create()->setPath(
                        'amasty_groupcat/*/edit',
                        ['id' => $this->getRequest()->getParam('id')]
                    )
                    : $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/new');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->logger->critical($e);
                $this->_getSession()->setPageData($data);
                $this->dataPersistor->set('amasty_groupcat_rule', $data);

                return $this->resultRedirectFactory->create()->setPath(
                    'amasty_groupcat/*/edit',
                    ['id' => $this->getRequest()->getParam('rule_id')]
                );
            }
        }

        return $this->resultRedirectFactory->create()->setPath('amasty_groupcat/*/');
    }

    private function prepareRuleDataForSave(array $ruleData): array
    {
        if (isset($ruleData['rule']['conditions'])) {
            $ruleData['conditions'] = $ruleData['rule']['conditions'];
        }

        if (isset($ruleData['rule']['actions'])) {
            $ruleData['actions'] = $ruleData['rule']['actions'];
        }

        unset($ruleData['rule']);
        $ruleData['customer_group_enabled'] = !empty($ruleData['customer_group_ids']);

        if (!$ruleData['customer_group_enabled']) {
            $ruleData['customer_group_ids'] = [];
        }

        if (!isset($ruleData['category_ids'])) {
            $ruleData['category_ids'] = [];
        }

        return $ruleData;
    }
}
