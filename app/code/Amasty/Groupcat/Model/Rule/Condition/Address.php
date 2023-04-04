<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition;

use Amasty\Groupcat\Utils\SqlConditionBuilder;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Address as AddressModel;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Directory\Model\Config\Source\Allregion;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;

class Address extends AbstractCondition implements SqlConditionInterface
{
    public const IS_UNDEFINED_OPERATOR = '<=>';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var Country
     */
    private $directoryCountry;

    /**
     * @var Allregion
     */
    private $directoryAllregion;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var SqlConditionBuilder
     */
    private $sqlConditionBuilder;

    /**
     * @var Config
     */
    private $eavConfig;

    public function __construct(
        Context $context,
        Country $directoryCountry,
        Allregion $directoryAllregion,
        ResourceConnection $resource,
        SqlConditionBuilder $sqlConditionBuilder,
        Config $eavConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->directoryCountry = $directoryCountry;
        $this->directoryAllregion = $directoryAllregion;
        $this->resource = $resource;
        $this->sqlConditionBuilder = $sqlConditionBuilder;
        $this->eavConfig = $eavConfig;
    }

    public function getAttributeElement(): AbstractElement
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    public function loadAttributeOptions(): Address
    {
        $attributes = [
            AddressInterface::POSTCODE => __('Shipping Postcode'),
            AddressInterface::COUNTRY_ID => __('Shipping Country'),
            AddressInterface::REGION_ID => __('Shipping State/Province'),
            AddressInterface::CITY => __('Shipping City'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getInputType(): string
    {
        switch ($this->getAttribute()) {
            case AddressInterface::POSTCODE:
                return 'numeric';
            case AddressInterface::COUNTRY_ID:
            case AddressInterface::REGION_ID:
                return 'select';
        }

        return 'string';
    }

    public function getValueElementType(): string
    {
        switch ($this->getAttribute()) {
            case AddressInterface::COUNTRY_ID:
            case AddressInterface::REGION_ID:
                return 'select';
        }

        return 'text';
    }

    public function getValueSelectOptions(): array
    {
        $selectOptions = [];
        $key = 'value_select_options';

        if (!$this->hasData($key)) {
            switch ($this->getAttribute()) {
                case AddressInterface::COUNTRY_ID:
                    $selectOptions = $this->directoryCountry->toOptionArray();
                    break;

                case AddressInterface::REGION_ID:
                    $selectOptions = $this->directoryAllregion->toOptionArray();
                    break;
            }
            $this->setData($key, $selectOptions);
        }

        return $this->getData($key);
    }

    public function getDefaultOperatorInputByType(): array
    {
        $operators = parent::getDefaultOperatorInputByType();
        $operators['string'] = ['==', '!=', '{}', '!{}', '<=>', '()', '!()'];
        $operators['numeric'] = ['==', '!=', '{}', '!{}'];

        return $operators;
    }

    public function validate(AbstractModel $customer): bool
    {
        /** @var Customer $customer */
        $address = $this->getAddress($customer);

        if ($address instanceof AbstractModel) {
            return parent::validate($address);
        }

        if (!$address) {
            /**
             * If customer doesn't have default address, then validate all addresses.
             * If one of the all addresses will be valid, then customer is valid.
             */
            foreach ($customer->getAddresses() as $address) {
                if (parent::validate($address)) {
                    return true;
                }
            }

            return $this->getOperator() === self::IS_UNDEFINED_OPERATOR;
        }

        return $this->validateAttribute($address);
    }

    /**
     * @param CustomerModel $customer
     * @return false|AddressModel|Customer
     */
    protected function getAddress(Customer $customer)
    {
        switch ($this->type) {
            case AddressHelper::TYPE_BILLING:
                return $customer->getDefaultBillingAddress();
            case AddressHelper::TYPE_SHIPPING:
                return $customer->getDefaultShippingAddress();
        }

        return $customer;
    }

    /**
     * @param mixed $validatedValue product attribute value
     * @return bool
     */
    public function validateAttribute($validatedValue): bool
    {
        if ($this->getOperator() === self::IS_UNDEFINED_OPERATOR) {
            return $validatedValue === null;
        }

        return parent::validateAttribute($validatedValue);
    }

    public function getAttributeObject(): AbstractAttribute
    {
        return $this->eavConfig->getAttribute('customer_address', $this->getAttribute());
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
     */
    public function getSatisfiedIds(array $params): array
    {
        $select = $this->getConditionsSelect(null, $params, false);

        return $this->resource->getConnection()->fetchCol($select);
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
    private function getCustomerConditionalSql($customer, array $params, bool $isFiltered): Select
    {
        $attribute = $this->getAttributeObject();
        $attributeTable = $attribute->getBackendTable();
        $select = $this->resource->getConnection()->select();

        if ($isFiltered) {
            $select->from(['main' => $attributeTable], [new \Zend_Db_Expr(1)]);
            $select->where($this->createEntityFilter($customer, 'main.parent_id'));
            $select->limit(1);
        } else {
            $select->from(['main' => $attributeTable], ['parent_id']);
        }

        if ($attribute->isStatic()) {
            $field = 'main.' . $attribute->getAttributeCode();
        } else {
            $select->where('main.attribute_id = ?', $attribute->getId());
            $field = 'main.value';
        }

        $field = $this->resource->getConnection()->quoteColumnAs($field, null);
        $condition = $this->sqlConditionBuilder->build(
            $field,
            $this->getOperator(),
            $this->getValue($attribute)
        );

        $select->where($condition);

        $groupIds = (array)($params['group_id'] ?? null);
        if ($groupIds) {
            $select->join(
                ['customer_entity' => $this->resource->getTableName('customer_entity')],
                'main.parent_id = customer_entity.entity_id',
                []
            );
            $select->where('customer_entity.group_id IN(?)', $groupIds);
        }

        return $select;
    }

    /**
     * Generate customer condition string
     *
     * @param DataObject|int|null $entity
     * @param string $fieldName
     * @return string
     */
    private function createEntityFilter($entity, string $fieldName): string
    {
        $customerId = is_object($entity) ? (int)$entity->getId() : (int)$entity;

        return $customerId
            ? $fieldName . ' = ' . $customerId
            : $fieldName . ' = root.entity_id';
    }
}
