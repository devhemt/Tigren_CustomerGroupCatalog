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

class Billing extends Address
{
    /**
     * @var string
     */
    protected $type = 'billing';

    public function loadAttributeOptions(): Address
    {
        $attributes = [
            AddressInterface::POSTCODE => __('Billing Postcode'),
            AddressInterface::COUNTRY_ID => __('Billing Country'),
            AddressInterface::REGION_ID => __('Billing State/Province'),
            AddressInterface::CITY => __('Billing City'),
            AddressInterface::COMPANY => __('Billing Company'),
            AddressInterface::VAT_ID => __('Billing Vat Number'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getNewChildSelectOptions(): array
    {
        $attributes = $this->loadAttributeOptions()->getAttributeOption();
        $conditions = [];
        foreach ($attributes as $code => $label) {
            $conditions[] = ['value' => Billing::class . '|' . $code, 'label' => $label];
        }

        return $conditions;
    }
}
