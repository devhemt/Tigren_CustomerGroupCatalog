<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule;

class PriceActionOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    public const SHOW = 0;
    public const HIDE = 1;
    public const REPLACE = 2;
    public const REPLACE_REQUEST = 3;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SHOW, 'label' => __('No')],
            ['value' => self::HIDE, 'label' => __('Yes')],
            ['value' => self::REPLACE, 'label' => __('Replace with text')],
            ['value' => self::REPLACE_REQUEST, 'label' => __('Replace to request form')]
        ];
    }
}
