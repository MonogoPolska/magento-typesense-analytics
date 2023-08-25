<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Services;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface as ScopeConfig;
use Magento\Store\Model\StoreManagerInterface;
use Monogo\TypesenseCore\Services\ConfigService as CoreConfigService;

class ConfigService extends CoreConfigService
{
    /**
     * Config paths
     */
    const TYPESENSE_ANALYTICS_ENABLED = 'typesense_analytics/settings/enabled';
    const TYPESENSE_ANALYTICS_INDEXES = 'typesense_analytics/settings/indexes';
    const TYPESENSE_ANALYTICS_AGGREGATION_SUFFIX = 'typesense_analytics/settings/aggregation_suffix';
    const TYPESENSE_ANALYTICS_QUERIES_LIMIT = 'typesense_analytics/settings/queries_limit';

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var array|null
     */
    protected ?array $selectedIndexes = null;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Manager $moduleManager
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        EncryptorInterface       $encryptor,
        ScopeConfigInterface     $scopeConfig,
        Manager                  $moduleManager,
        SerializerInterface      $serializer,
        StoreManagerInterface    $storeManager
    )
    {
        parent::__construct(
            $productMetadata,
            $encryptor,
            $scopeConfig,
            $moduleManager,
            $serializer
        );
        $this->storeManager = $storeManager;
    }

    /**
     * @param $storeId
     * @return bool|null
     */
    public function isEnabled($storeId = null): ?bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::TYPESENSE_ANALYTICS_ENABLED,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getSelectedIndexes($storeId = null): ?array
    {
        if (is_null($this->selectedIndexes)) {
            $this->selectedIndexes = [];
            $data = $this->scopeConfig->getValue(
                self::TYPESENSE_ANALYTICS_INDEXES,
                ScopeConfig::SCOPE_STORE,
                $storeId
            );
            $data = explode(',', $data);
            $stores = $this->storeManager->getStores(true);

            foreach ($data as $index) {

                /** \Magento\Store\Api\Data\StoreInterface $store **/
                foreach ($stores as $store) {
                    $this->selectedIndexes[] = $this->getBaseIndexName((int)$store->getId()) . $index;
                }

            }
        }
        return $this->selectedIndexes;
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseIndexName(?int $storeId = null): string
    {
        return $this->getIndexPrefix($storeId) . $this->storeManager->getStore($storeId)->getCode();
    }

    /**
     * @param $storeId
     * @return string|null
     */
    public function getAggregationSuffix($storeId = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::TYPESENSE_ANALYTICS_AGGREGATION_SUFFIX,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
        if (empty($value)) {
            $value = '_aggregation';
        }

        return $value;
    }

    /**
     * @param $storeId
     * @return int|null
     */
    public function getQueriesLimit($storeId = null): ?int
    {
        return (int)$this->scopeConfig->getValue(
            self::TYPESENSE_ANALYTICS_QUERIES_LIMIT,
            ScopeConfig::SCOPE_STORE,
            $storeId
        );
    }
}
