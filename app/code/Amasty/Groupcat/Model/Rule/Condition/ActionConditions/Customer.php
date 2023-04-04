<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\ActionConditions;

use Amasty\Groupcat\Model\Rule\Condition\SqlConditionInterface;
use Amasty\Groupcat\Utils\SqlConditionBuilder;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\Attribute\Backend\Data\Boolean;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

class Customer extends AbstractCondition implements SqlConditionInterface
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var CustomerResourceModel
     */
    private $resourceCustomer;

    /**
     * @var Yesno
     */
    private $yesnoOptions;

    /**
     * @var SqlConditionBuilder
     */
    private $sqlConditionBuilder;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        CustomerResourceModel $resourceCustomer,
        Yesno $yesnoOptions,
        Config $eavConfig,
        SqlConditionBuilder $sqlConditionBuilder,
        array $data = []
    ) {
        $this->resourceCustomer = $resourceCustomer;
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->eavConfig = $eavConfig;
        $this->yesnoOptions = $yesnoOptions;
        $this->sqlConditionBuilder = $sqlConditionBuilder;
    }

    /**
     * Customize default operator input by type mapper for some types.
     *
     * @return array
     */
    public function getDefaultOperatorInputByType(): array
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
            $this->_defaultOperatorInputByType['multiselect'] = ['==', '!=', '[]', '![]'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Default operator options getter.
     *
     * Provides all possible operator options.
     *
     * @return array
     */
    public function getDefaultOperatorOptions(): array
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = parent::getDefaultOperatorOptions();

            $this->_defaultOperatorOptions['[]'] = __('contains');
            $this->_defaultOperatorOptions['![]'] = __('does not contains');
        }

        return $this->_defaultOperatorOptions;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions(): array
    {
        $attributes = $this->loadAttributeOptions()->getAttributeOption();
        $conditions = [];
        foreach ($attributes as $code => $label) {
            $conditions[] = ['value' => Customer::class . '|' . $code, 'label' => $label];
        }

        return $conditions;
    }

    /**
     * Retrieve attribute object
     *
     * @return Attribute|Attribute\AbstractAttribute
     */
    public function getAttributeObject()
    {
        return $this->eavConfig->getAttribute('customer', $this->getAttribute());
    }

    /**
     * Load condition options for castomer attributes
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $customerAttributes = $this->resourceCustomer->loadAllAttributes()->getAttributesByCode();
        $attributes = [];

        foreach ($customerAttributes as $attribute) {
            $label = $attribute->getFrontendLabel();
            if (!$label) {
                continue;
            }

            // skip "binary" attributes
            if (in_array($attribute->getFrontendInput(), ['file', 'image'])) {
                continue;
            }

            $attributes[$attribute->getAttributeCode()] = $label;
        }

        $this->addSpecialAttributes($attributes);
        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * Add special attributes
     *
     * @param array &$attributes
     * @return void
     */
    private function addSpecialAttributes(array &$attributes)
    {
        $attributes['entity_id'] = __('Customer ID');
    }

    /**
     * Retrieve select option values
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        if (!$this->getData('value_select_options')) {
            $optionsArr = [];
            if ($this->getAttributeObject()->usesSource()) {
                if ($this->getAttributeObject()->getFrontendInput() == 'multiselect') {
                    $addEmptyOption = false;
                } else {
                    $addEmptyOption = true;
                }

                $optionsArr = $this->getAttributeObject()->getSource()->getAllOptions($addEmptyOption);
            }

            if ($this->isBooleanType() && count($optionsArr) === 0) {
                $optionsArr = $this->yesnoOptions->toOptionArray();
            }

            $this->setData('value_select_options', $optionsArr);
        }

        return $this->getData('value_select_options');
    }

    /**
     * Get input type for attribute operators.
     *
     * @return string
     */
    public function getInputType()
    {
        if (!is_object($this->getAttributeObject())) {
            return 'string';
        }

        if ($this->getAttribute() === 'entity_id') {
            return 'grid';
        }

        $input = $this->getAttributeObject()->getFrontendInput();
        switch ($input) {
            case 'boolean':
                return 'select';
            case 'select':
            case 'multiselect':
            case 'date':
                return $input;
            case 'gallery':
            case 'media_image':
            case 'selectimg': // amasty customer attribute
                return 'select';
            case 'multiselectimg': // amasty customer attribute
                return 'multiselect';
            default:
                return 'string';
        }
    }

    /**
     * Get value element.
     *
     * @return $this
     */
    public function getValueElement()
    {
        $element = parent::getValueElement();
        switch ($this->getInputType()) {
            case 'date':
                $element->setClass('hasDatepicker');
                break;
        }

        return $element;
    }

    /**
     * Get attribute value input element type
     *
     * @return string
     */
    public function getValueElementType(): string
    {
        if (!is_object($this->getAttributeObject()) || $this->getAttribute() === 'entity_id') {
            return 'text';
        }

        $availableTypes = [
            'checkbox',
            'checkboxes',
            'date',
            'editablemultiselect',
            'editor',
            'fieldset',
            'file',
            'gallery',
            'image',
            'imagefile',
            'multiline',
            'multiselect',
            'radio',
            'radios',
            'select',
            'text',
            'textarea',
            'time'
        ];

        $input = $this->getAttributeObject()->getFrontendInput();
        if (in_array($input, $availableTypes)) {
            return $input;
        }

        switch ($input) {
            case 'selectimg':
            case 'boolean':
                return 'select';
            case 'multiselectimg':
                return 'multiselect';
            default:
                return 'text';
        }
    }

    /**
     * Retrieve attribute element
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    /**
     * Check that entity matched with parameters by condition
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($entity, array $params): bool
    {
        $select = $this->getConditionsSelect($entity, $params);
        $result = (int)$this->resource->getConnection()->fetchOne($select);

        return $result > 0;
    }

    /**
     * Return Ids according to condition.
     *
     * @param array $params
     * @return array
     */
    public function getSatisfiedIds(array $params): array
    {
        $select = $this->getConditionsSelect(null, $params, false);

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * Generate customer condition string
     *
     * @param DataObject|int|null $entity
     * @param string $fieldName
     * @return string
     */
    protected function createEntityFilter($entity, string $fieldName): string
    {
        $customerId = is_object($entity) ? (int)$entity->getId() : (int)$entity;

        return $customerId
            ? $fieldName . ' = ' . $customerId
            : $fieldName . ' = root.entity_id';
    }

    /**
     * Create SQL condition select for customer attribute
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    public function getConditionsSelect($entity, array $params, bool $isFiltered = true): Select
    {
        return $this->getCustomerConditionalSql($entity, $params, $isFiltered);
    }

    /**
     * Create SQL condition select for customer attribute
     *
     * @param CustomerModel|int|null $customer
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    private function getCustomerConditionalSql($customer, array $params, $isFiltered): Select
    {
        $attribute = $this->getAttributeObject();
        $attributeTable = $attribute->getBackendTable();
        $select = $this->resource->getConnection()->select();

        if ($isFiltered) {
            $select->from(['main' => $attributeTable], [new \Zend_Db_Expr(1)]);
            $select->where($this->createEntityFilter($customer, 'main.entity_id'));
            $select->limit(1);
        } else {
            $select->from(['main' => $attributeTable], ['entity_id']);
        }

        if ($attribute->isStatic()) {
            $field = 'main.' . $attribute->getAttributeCode();
        } else {
            $select->where('main.attribute_id = ?', $attribute->getId());
            $field = 'main.value';
        }
        $field = $this->resource->getConnection()->quoteColumnAs($field, null);

        if (in_array($attribute->getAttributeCode(), ['default_billing', 'default_shipping'])) {
            if (!$isFiltered) {
                return $this->getCustomerSelect($params);
            }

            $condition = $this->buildAddressCondition($select, $field);
        } else {
            $condition = $this->sqlConditionBuilder->build(
                $field,
                $this->getMappedOperator($attribute),
                $this->getMappedValue($attribute)
            );
        }
        $select->where($condition);

        $groupIds = (array)($params['group_id'] ?? null);
        if ($groupIds) {
            $entityTable = 'main';
            if ($attributeTable != $attribute->getEntity()->getEntityTable()) {
                $select->join(
                    ['entity_table' => $attribute->getEntity()->getEntityTable()],
                    'main.entity_id = entity_table.entity_id',
                    []
                );
                $entityTable = 'entity_table';
            }
            $select->where($entityTable . '.group_id IN(?)', $groupIds);
        }

        return $select;
    }

    /**
     * Build SQL condition for billing or shipping address.
     *
     * @param Select $select
     * @param string $field
     * @return string
     */
    private function buildAddressCondition(Select $select, string $field): string
    {
        $select->joinLeft(
            ['address_entity' => $this->resource->getTableName('customer_address_entity')],
            $field . ' = address_entity.entity_id',
            []
        )->joinLeft(
            ['region' => $this->resource->getTableName('directory_country_region')],
            'address_entity.region_id = region.region_id',
            []
        )->joinLeft(
            ['region_name' => $this->resource->getTableName('directory_country_region_name')],
            'address_entity.region_id = region_name.region_id',
            []
        );
        $conditionExpression = $this->resource->getConnection()->getConcatSql(
            [
                'address_entity.city',
                'address_entity.country_id',
                'address_entity.postcode',
                $this->resource->getConnection()->getIfNullSql(
                    'region_name.name',
                    'region.default_name'
                ),
                'address_entity.street'
            ],
            ','
        );

        return $this->sqlConditionBuilder->build(
            $conditionExpression,
            $this->getOperator(),
            $this->getValue(),
            true
        );
    }

    /**
     * Process condition operator by attribute type and return mapped operator value
     *
     * @param Attribute $attribute
     * @return string
     */
    private function getMappedOperator(Attribute $attribute): string
    {
        $operator = (string)$this->getOperator();

        if ($attribute->getFrontendInput() == 'date'
            && $operator == '=='
        ) {
            return 'between';
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && $operator == '=='
        ) {
            return 'finset';
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && $operator == '!='
        ) {
            return '!finset';
        }

        return $operator;
    }

    /**
     * Process attribute value by attribute type and return mapped attribute value
     *
     * @param Attribute $attribute
     * @return array
     */
    private function getMappedValue(Attribute $attribute)
    {
        $operator = $this->getOperator();
        $value = $this->getValue();

        if ($attribute->getFrontendInput() == 'date'
            && $operator == '=='
        ) {
            $dateObj = (new \DateTime($this->getValue()))->setTime(0, 0, 0);
            $value = [
                'start' => $dateObj->format('Y-m-d H:i:s'),
                'end' => $dateObj->modify('+1 day')->format('Y-m-d H:i:s'),
            ];
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && is_array($value)
        ) {
            array_walk(
                $value,
                function (&$value) {
                    $value = (int)$value;
                }
            );
        }

        return $value;
    }

    /**
     * Return customer select according to condition.
     *
     * @param array $params
     * @return Select
     */
    private function getCustomerSelect(array $params): Select
    {
        $select = $this->resource->getConnection()->select();
        $table = $this->resource->getTableName('customer_entity');
        $select->from(['root' => $table], ['entity_id']);
        $select->where($this->getCustomerConditionalSql(null, $params, true));

        return $select;
    }

    private function isBooleanType(): bool
    {
        return $this->getInputType() === 'boolean'
            || $this->getAttributeObject()->getBackendModel() === Boolean::class;
    }
}
