<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Observer;

use Magento\Framework\DB\Select;

trait CatalogCollectionTrait
{
    /**
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     * @param int[]|null $ids
     */
    protected function restrictCollectionIds($collection, $ids)
    {
        if (is_array($ids) && count($ids)) {
            $alias = '';

            if ($collection instanceof \Magento\Catalog\Model\ResourceModel\Category\Flat\Collection) {
                $alias = $this->getMainAlias($collection->getSelect());
            }

            $idField = $alias . $this->getIdFieldName($collection);
            $collection->addFieldToFilter($idField, ['nin' => $ids]);
        }
    }

    /**
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection
     *
     * @return string
     */
    protected function getIdFieldName($collection)
    {
        if (method_exists($collection, 'getRowIdFieldName')) {
            return $collection->getRowIdFieldName();
        }

        return $collection->getResource()->getIdFieldName();
    }

    /**
     * @param $select
     * @return string
     * @throws \Zend_Db_Select_Exception
     */
    protected function getMainAlias($select)
    {
        $from = $select->getPart(Select::FROM);
        foreach ($from as $alias => $data) {
            if ($data['joinType'] == 'from') {
                return $alias . '.';
            }
        }

        return '';
    }
}
