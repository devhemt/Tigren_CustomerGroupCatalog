<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition\Address;

use Amasty\Groupcat\Model\Rule\Condition\Address;
use Magento\Customer\Api\Data\AddressInterface;

class Shipping extends Address
{
    /**
     * @var string
     */
    protected $type = 'shipping';

    public function loadAttributeOptions(): Address
    {
        $attributes = [
            AddressInterface::POSTCODE => __('Shipping Postcode'),
            AddressInterface::COUNTRY_ID => __('Shipping Country'),
            AddressInterface::REGION_ID => __('Shipping State/Province'),
            AddressInterface::CITY => __('Shipping City'),
            AddressInterface::COMPANY => __('Shipping Company'),
            AddressInterface::VAT_ID => __('Shipping Vat Number'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getNewChildSelectOptions(): array
    {
        $attributes = $this->loadAttributeOptions()->getAttributeOption();
        $conditions = [];
        foreach ($attributes as $code => $label) {
            $conditions[] = ['value' => Shipping::class . '|' . $code, 'label' => $label];
        }

        return $conditions;
    }
}
