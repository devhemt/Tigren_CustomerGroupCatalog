<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Block;

use Amasty\Groupcat\Block\Framework\Pricing\RequestPopup;
use Amasty\Groupcat\Helper\Data as Config;
use Amasty\Groupcat\Model\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

class Form extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Template\Context $context,
        Config $config, // @deprecated. Backward compatibility
        ConfigProvider $configProvider = null,
        StoreManagerInterface $storeManager = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->configProvider = $configProvider ?? ObjectManager::getInstance()->get(ConfigProvider::class);
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * @return Config
     * @deprecated Use the \Amasty\Groupcat\Model\ConfigProvider class instead.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getFrontendLink(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return $this->configProvider->getFrontendLink($storeId);
    }

    public function isPopup(): bool
    {
        return trim($this->getFrontendLink()) === RequestPopup::HIDE_PRICE_POPUP_LINK_KEY;
    }

    public function getGDPRText(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return $this->filterManager->stripTags(
            $this->configProvider->getGDPRText($storeId),
            [
                'allowableTags' => '<a>',
                'escape' => false
            ]
        );
    }
}
