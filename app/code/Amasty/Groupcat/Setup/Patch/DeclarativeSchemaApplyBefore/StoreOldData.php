<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Setup\Patch\DeclarativeSchemaApplyBefore;

use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class StoreOldData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceInterface $moduleResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->moduleResource = $moduleResource;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply()
    {
        $setupDataVersion = $this->moduleResource->getDataVersion('Amasty_Groupcat');

        if ($setupDataVersion
            && version_compare($setupDataVersion, '1.2.0', '<')
            && $this->moduleDataSetup->tableExists($this->moduleDataSetup->getTable('amasty_amgroupcat_rules'))
        ) {
            $this->moduleDataSetup->getConnection()->renameTable(
                $this->moduleDataSetup->getTable('amasty_amgroupcat_rules'),
                $this->moduleDataSetup->getTable('amasty_amgroupcat_rules_old')
            );
        }
    }
}
