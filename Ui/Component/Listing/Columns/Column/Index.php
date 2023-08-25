<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Ui\Component\Listing\Columns\Column;

class Index extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['index'] = $item['index_name'];
            }
        }
        return $dataSource;
    }
}
