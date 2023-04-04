<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model\Rule\Condition;

use Magento\Framework\View\LayoutInterface;

class TooltipRenderer
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string|null
     */
    private $tooltipTemplate;

    public function __construct(
        LayoutInterface $layout,
        string $tooltipTemplate = null
    ) {
        $this->layout = $layout;
        $this->tooltipTemplate = $tooltipTemplate;
    }

    public function renderTooltip(): string
    {
        if ($this->tooltipTemplate) {
            return $this->layout->createBlock(\Magento\Backend\Block\Template::class)
                ->setTemplate($this->tooltipTemplate)
                ->toHtml();
        }

        return '';
    }
}
