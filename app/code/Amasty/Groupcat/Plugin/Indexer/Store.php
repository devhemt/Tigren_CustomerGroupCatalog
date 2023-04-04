<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Plugin\Indexer;

use Amasty\Groupcat\Model\Indexer\Rule\RuleProductProcessor;
use Magento\Framework\App\ScopeInterface;

class Store
{
    /**
     * @var RuleProductProcessor
     */
    private $ruleProductProcessor;

    public function __construct(RuleProductProcessor $ruleProductProcessor)
    {
        $this->ruleProductProcessor = $ruleProductProcessor;
    }

    public function afterDelete(
        ScopeInterface $subject,
        ScopeInterface $result
    ): ScopeInterface {
        $this->ruleProductProcessor->markIndexerAsInvalid();

        return $result;
    }
}
