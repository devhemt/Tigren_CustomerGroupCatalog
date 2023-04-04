<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\OptionSource\Rule;

use Magento\Framework\Data\OptionSourceInterface;

class PriceAction implements OptionSourceInterface
{
    public const SHOW = 0;
    public const HIDE = 1;
    public const REPLACE = 2;
    public const REPLACE_TO_REQUEST_FORM = 3;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::SHOW, 'label' => __('Show')],
            ['value' => self::HIDE, 'label' => __('Hide')],
            ['value' => self::REPLACE, 'label' => __('Replace')],
            ['value' => self::REPLACE_TO_REQUEST_FORM, 'label' => __('Replace to request form')]
        ];
    }
}
