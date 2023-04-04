<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Source;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    public const PENDING = 0;
    public const VIEWED = 1;
    public const ANSWERED = 2;

    /**
     * @var array
     */
    protected $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [
                [
                    'value' => self::PENDING,
                    'label' => __('Pending')
                ],
                [
                    'value' => self::VIEWED,
                    'label' => __('Viewed')
                ],
                [
                    'value' => self::ANSWERED,
                    'label' => __('Answered')
                ]
            ];
        }

        return $this->_options;
    }

    public function getOptionByValue($value)
    {
        $options = $this->toOptionArray();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }

        return '';
    }
}
