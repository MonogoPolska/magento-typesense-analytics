<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Monogo\TypesenseAnalytics\Services\ConfigService;
use Monogo\TypesenseAnalytics\Services\TypesenseService;

class HandleNewAlias implements ObserverInterface
{
    /**
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * @var TypesenseService
     */
    protected TypesenseService $typesenseService;

    /**
     * @param ConfigService $configService
     * @param TypesenseService $typesenseService
     */
    public function __construct(ConfigService $configService, TypesenseService $typesenseService)
    {
        $this->configService = $configService;
        $this->typesenseService = $typesenseService;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configService->isEnabled()) {
            return;
        }

        $selectedIndexers = $this->configService->getSelectedIndexes();
        $alias = $observer->getData('alias');
        $collection = $observer->getData('collection');
        if (!in_array($alias, $selectedIndexers)) {
            return;
        }

        $aggregationIndexName = $this->typesenseService->getAggregationIndexName($alias);
        $this->typesenseService->setAnalyticsRule($aggregationIndexName, $collection);
    }
}
