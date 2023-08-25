<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Ui\DataProvider\Analytics;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Monogo\TypesenseAnalytics\Ui\DataProvider\Analytics\Listing\CollectionFactory;

class ListingDataProvider extends AbstractDataProvider
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        string            $name,
        string            $primaryFieldName,
        string            $requestFieldName,
        array             $meta = [],
        array             $data = []
    )
    {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getData()
    {
        $this->collection->prepareCollection();
        return parent::getData();
    }
}
