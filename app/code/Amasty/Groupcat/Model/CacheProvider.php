<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Amasty\Groupcat\Model;

use Amasty\Base\Model\Serializer;
use Magento\Framework\App\Cache\Type\Collection as CacheCollection;

class CacheProvider
{
    /**
     * @var CacheCollection
     */
    private $collectionCache;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        CacheCollection $collectionCache,
        Serializer $serializer
    ) {
        $this->collectionCache = $collectionCache;
        $this->serializer = $serializer;
    }

    public function load(string $cacheId): array
    {
        $cachedData = $this->collectionCache->load($cacheId) ?: '';

        return $this->serializer->unserialize($cachedData) ?: [];
    }

    public function save(array $itemIds, string $cacheId): void
    {
        $this->collectionCache->save(
            $this->serializer->serialize($itemIds),
            $cacheId,
            [],
            3600 // some rules have data range. we should check data range again after 1 hour
        );
    }
}
