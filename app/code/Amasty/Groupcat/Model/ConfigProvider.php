<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Amasty\Groupcat\Block\Framework\Pricing\RequestPopup;

class ConfigProvider extends ConfigProviderAbstract
{
    public const ENABLED = 'general/enabled';
    public const FRONTEND_LINK = 'frontend/link';
    public const GDPR_TEXT = 'gdpr/text';

    /**
     * @var string
     */
    protected $pathPrefix = 'amasty_groupcat/';

    public function isEnabled(): bool
    {
        return $this->isSetFlag(self::ENABLED);
    }

    public function getFrontendLink(?int $storeId = null): string
    {
        return $this->getValue(self::FRONTEND_LINK, $storeId) ?: RequestPopup::HIDE_PRICE_POPUP_LINK_KEY;
    }

    public function getGDPRText(?int $storeId = null): string
    {
        return (string) $this->getValue(self::GDPR_TEXT, $storeId);
    }
}
