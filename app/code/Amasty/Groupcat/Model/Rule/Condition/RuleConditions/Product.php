<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\RuleConditions;

use Amasty\Groupcat\Model\Rule\Condition\SqlConditionInterface;
use Amasty\Groupcat\Utils\SqlConditionBuilder;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Locale\FormatInterface;
use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

class Product extends AbstractProduct implements SqlConditionInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var MetadataPool
     */
    private $entityManagerMetadataPool;

    /**
     * @var SqlConditionBuilder
     */
    private $sqlConditionBuilder;

    /**
     * @var string
     */
    private $productLinkField;

    public function __construct(
        Context $context,
        Data $backendData,
        Config $config,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductResourceModel $productResource,
        Collection $attrSetCollection,
        FormatInterface $localeFormat,
        ResourceConnection $resource,
        MetadataPool $metadataPool,
        SqlConditionBuilder $sqlConditionBuilder,
        array $data = []
    ) {
        $this->resource = $resource;
        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data
        );
        $this->entityManagerMetadataPool = $metadataPool;
        $this->sqlConditionBuilder = $sqlConditionBuilder;
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType(): array
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
            $this->_defaultOperatorInputByType['sku'] = ['==', '!=', '{}', '!{}', '()', '!()'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Get input type for attribute operators.
     *
     * @return string
     */
    public function getInputType(): string
    {
        if (!is_object($this->getAttributeObject())) {
            return 'string';
        }

        $attributeCode = $this->getAttributeObject()->getAttributeCode();
        switch ($attributeCode) {
            case 'sku':
                return 'sku';
            case 'category_ids':
                return 'category';
        }

        $input = $this->getAttributeObject()->getFrontendInput();
        switch ($input) {
            case 'select':
            case 'multiselect':
            case 'date':
                return $input;
            default:
                return 'string';
        }
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
            $conditions[] = ['value' => Product::class . '|' . $code, 'label' => $label];
        }

        return $conditions;
    }

    /**
     * Get product attribute object
     *
     * @return AbstractAttribute
     */
    public function getAttributeObject()
    {
        return $this->_config->getAttribute('catalog_product', $this->getAttribute());
    }

    /**
     * Check that product matched with parameters by condition
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
        $productId = is_object($entity) ? (int)$entity->getId() : (int)$entity;

        return $productId
            ? $fieldName . ' = ' . $productId
            : $fieldName . ' = main.entity_id';
    }

    /**
     * Get product entity link field name.
     *
     * @return string
     */
    private function getProductLinkField(): string
    {
        if (!$this->productLinkField) {
            $productMetadata = $this->entityManagerMetadataPool->getMetadata(ProductInterface::class);
            $this->productLinkField = $productMetadata->getLinkField();
        }

        return $this->productLinkField;
    }

    /**
     * Create SQL condition select for product attribute
     *
     * @param DataObject|int|null $entity
     * @param array $params
     * @param bool $isFiltered
     * @return Select
     */
    public function getConditionsSelect($entity, array $params, bool $isFiltered = true): Select
    {
        $select = $this->resource->getConnection()->select();
        $linkField = $this->getProductLinkField();
        $attribute = $this->getAttributeObject();
        $attributeTable = $attribute->getBackendTable();
        $productTable = $this->resource->getTableName('catalog_product_entity');
        $attributeTableAlias = $this->getAttributeTableAlias();

        if ($isFiltered) {
            $select->from(['main' => $productTable], [new \Zend_Db_Expr(1)]);
            $select->where($this->createEntityFilter($entity, 'main.entity_id'));
            $select->limit(1);
        } else {
            $select->from(['main' => $productTable], ['entity_id']);
        }

        if ($productTable !== $attributeTable) {
            // If attribute backend table is different from 'catalog_product_entity', then 'catalog_product_entity'
            // should be joined to be able to filter by 'entity_id' with enabled staging
            $select->join(
                [$this->getAttributeTableAlias() => $attributeTable],
                $attributeTableAlias . '.' . $linkField . ' = main.' . $linkField,
                []
            );
        }

        if ($attribute->getAttributeCode() == 'category_ids') {
            $condition = $this->buildCategoryIdsCondition();
        } elseif ($attribute->isStatic()) {
            $condition = $this->buildStaticAttributeCondition();
        } else {
            $select->where($attributeTableAlias . '.attribute_id = ?', $attribute->getId())
                ->where($attributeTableAlias . '.store_id IN (?)', [0, $params['store_id'] ?? 0]);
            $condition = $this->buildNonStaticAttributeCondition();
        }
        $select->where($condition);

        $websiteIds = (array)($params['website_id'] ?? null);
        if ($websiteIds) {
            $select->joinLeft(
                ['product_website' => $this->resource->getTableName('catalog_product_website')],
                'product_website.product_id = main.entity_id',
                []
            );
            $select->where('product_website.website_id IN(?)', $websiteIds);
        }

        return $select;
    }

    /**
     * Build SQL condition for 'category_ids' attribute.
     *
     * @return string
     */
    private function buildCategoryIdsCondition(): string
    {
        $categorySelectCondition = $this->sqlConditionBuilder->build(
            'cat.category_id',
            (string)$this->getOperator(),
            $this->getValueParsed()
        );
        $categorySelect = $this->resource->getConnection()->select();
        $categorySelect->from(
            ['cat' => $this->resource->getTableName('catalog_category_product')],
            'product_id'
        )->where($categorySelectCondition);
        $entityIds = implode(',', $this->resource->getConnection()->fetchCol($categorySelect));

        return empty($entityIds) ? 'FALSE' : 'main.entity_id IN (' . $entityIds . ')';
    }

    /**
     * Build SQL condition for non-static attribute.
     *
     * @return string
     */
    private function buildNonStaticAttributeCondition(): string
    {
        return $this->sqlConditionBuilder->build(
            $this->getAttributeTableAlias() . '.value',
            (string)$this->getOperator(),
            $this->getValue()
        );
    }

    /**
     * Build SQL condition for static attribute.
     *
     * @return string
     */
    private function buildStaticAttributeCondition(): string
    {
        $attribute = $this->getAttributeObject();

        return $this->sqlConditionBuilder->build(
            $this->getAttributeTableAlias() . '.' . $attribute->getAttributeCode(),
            (string)$this->getOperator(),
            $this->getValue()
        );
    }

    /**
     * Get alias for the table holding attribute data.
     *
     * @return string
     */
    private function getAttributeTableAlias(): string
    {
        $attributeTable = $this->getAttributeObject()->getBackendTable();
        $productTable = $this->resource->getTableName('catalog_product_entity');

        return ($productTable === $attributeTable) ? 'main' : 'eav_attribute';
    }
}
