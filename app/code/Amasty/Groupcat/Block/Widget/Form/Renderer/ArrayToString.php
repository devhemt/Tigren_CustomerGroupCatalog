<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Block\Widget\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Element;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Form element renderer
 */
class ArrayToString extends Element implements RendererInterface
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (is_array($element->getValue())) {
            $element->setValue(implode(',', $element->getValue()));
        }
        return parent::render($element);
    }
}
