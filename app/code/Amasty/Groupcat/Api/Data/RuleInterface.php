<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Api\Data;

interface RuleInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const RULE_ID = 'rule_id';
    public const NAME = 'name';
    public const IS_ACTIVE = 'is_active';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const FORBIDDEN_ACTION = 'forbidden_action';
    public const FORBIDDEN_PAGE_ID = 'forbidden_page_id';
    public const ALLOW_DIRECT_LINKS = 'allow_direct_links';
    public const HIDE_PRODUCT = 'hide_product';
    public const APPLY_TO_OUT_OF_STOCK = 'apply_to_out_of_stock';
    public const HIDE_CATEGORY = 'hide_category';
    public const HIDE_CART = 'hide_cart';
    public const HIDE_WISHLIST = 'hide_wishlist';
    public const HIDE_COMPARE = 'hide_compare';
    public const PRICE_ACTION = 'price_action';
    public const BLOCK_ID_VIEW = 'block_id_view';
    public const BLOCK_ID_LIST = 'block_id_list';
    public const FROM_DATE = 'from_date';
    public const TO_DATE = 'to_date';
    public const DATE_RANGE_ENABLED = 'date_range_enabled';
    public const CUSTOMER_GROUP_ENABLED = 'customer_group_enabled';
    public const PRIORITY = 'priority';
    /**#@-*/

    /**
     * Returns rule id field
     *
     * @return int|null
     */
    public function getRuleId();

    /**
     * @param int $ruleId
     *
     * @return $this
     */
    public function setRuleId($ruleId);

    /**
     * Returns rule name
     *
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Returns rule activity flag
     *
     * @return int
     */
    public function getIsActive();

    /**
     * @param int $isActive
     *
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * @return int
     */
    public function getForbiddenAction();

    /**
     * @param int $action
     *
     * @return $this
     */
    public function setForbiddenAction($action);

    /**
     * @return int|null
     */
    public function getForbiddenPageId();

    /**
     * @param int|null $cmsPageId
     *
     * @return $this
     */
    public function setForbiddenPageId($cmsPageId);

    /**
     * @return int
     */
    public function getAllowDirectLinks();

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setAllowDirectLinks($flag);

    /**
     * @return int
     */
    public function getHideProduct();

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setHideProduct($flag);

    /**
     * @return int
     */
    public function getApplyToOutOfStock(): int;

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setApplyToOutOfStock($flag): self;

    /**
     * @return int
     */
    public function getHideCategory();

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setHideCategory($flag);

    /**
     * @return int
     */
    public function getHideCart();

    /**
     * @param int $option
     *
     * @return $this
     */
    public function setHideCart($option);

    /**
     * @return int
     */
    public function getHideWishlist();

    /**
     * @param int $option
     *
     * @return $this
     */
    public function setHideWishlist($option);

    /**
     * @return int
     */
    public function getHideCompare();

    /**
     * @param int $option
     *
     * @return $this
     */
    public function setHideCompare($option);

    /**
     * @return int
     */
    public function getPriceAction();

    /**
     * @param int $option
     *
     * @return $this
     */
    public function setPriceAction($option);

    /**
     * @return int|null
     */
    public function getBlockIdView();

    /**
     * @param int|null $cmsBlockId
     *
     * @return $this
     */
    public function setBlockIdView($cmsBlockId);

    /**
     * @return int|null
     */
    public function getBlockIdList();

    /**
     * @param int|null $cmsBlockId
     *
     * @return $this
     */
    public function setBlockIdList($cmsBlockId);

    /**
     * @return string
     */
    public function getFromDate();

    /**
     * @param string $date
     *
     * @return $this
     */
    public function setFromDate($date);

    /**
     * @return string
     */
    public function getToDate();

    /**
     * @param string $date
     *
     * @return $this
     */
    public function setToDate($date);

    /**
     * @return int
     */
    public function getDateRangeEnabled();

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setDateRangeEnabled($flag);

    /**
     * @return int
     */
    public function getCustomerGroupEnabled();

    /**
     * @param int|bool $flag
     *
     * @return $this
     */
    public function setCustomerGroupEnabled($flag);

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority);
}
