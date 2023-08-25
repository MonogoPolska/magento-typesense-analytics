<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Model\Config\Source;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Option\ArrayInterface;
use Monogo\TypesenseAnalytics\Services\TypesenseService;

class IndexName implements ArrayInterface
{
    /**
     * @var TypesenseService
     */
    protected TypesenseService $typesenseService;

    /**
     * @param TypesenseService $typesenseService
     */
    public function __construct(TypesenseService $typesenseService)
    {
        $this->typesenseService = $typesenseService;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        $options = [];
        $collection = $this->typesenseService->getAggregationCollections();

        foreach ($collection as $index => $name) {
            $options[] = ['label' => $name, 'value' => $index];
        }
        return $options;
    }
}
