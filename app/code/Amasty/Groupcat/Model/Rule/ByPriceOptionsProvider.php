<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule;

class ByPriceOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    public const BASE_PRICE = 0;
    public const FINAL_PRICE = 1;
    public const MINIMAL_PRICE = 4;
    public const MAXIMAL_PRICE = 5;

    /**
     * @var array|null
     */
    protected $options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => self::BASE_PRICE, 'label' => __('Base Price')],
                ['value' => self::FINAL_PRICE, 'label' => __('Final Price')],
                ['value' => self::MINIMAL_PRICE, 'label' => __('Starting from Price')],
                ['value' => self::MAXIMAL_PRICE, 'label' => __('Starting to Price')],
            ];
        }

        return $this->options;
    }
}
