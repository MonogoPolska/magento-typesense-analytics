<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Ui\DataProvider\Analytics\Listing;

use Exception;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Monogo\TypesenseAnalytics\Services\TypesenseService;

class Collection extends DataCollection
{
    /**
     * @var TypesenseService
     */
    protected TypesenseService $typesenseService;

    /**
     * @var string|null
     */
    protected ?string $orderField = null;

    /**
     * @var string|null
     */
    protected ?string $orderDirection = null;

    /**
     * @var array|null
     */
    protected ?array $filter = [];

    /**
     * @var int
     */
    protected int $curentPage = 1;

    /**
     * @var int|null
     */
    protected ?int $pageSize = null;


    /**
     * @param EntityFactoryInterface $entityFactory
     * @param TypesenseService $typesenseService
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        TypesenseService       $typesenseService

    )
    {
        $this->typesenseService = $typesenseService;
        parent::__construct($entityFactory);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function prepareCollection(): void
    {
        $this->removeAllItems();
        $data = $this->typesenseService->getAnalyticsData($this->getParams());
        foreach ($data as $item) {
            $this->addItem(new DataObject($item));
        }
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return [
            'order_by' => $this->orderField,
            'order_direction' => $this->orderDirection,
            'page' => $this->curentPage,
            'page_size' => $this->pageSize,
            'filter' => $this->filter,
        ];
    }

    /**
     * @param $page
     * @return $this|Collection
     */
    public function setCurPage($page): self
    {
        $this->curentPage = $page;
        return $this;
    }

    /**
     * @param $size
     * @return $this|Collection
     */
    public function setPageSize($size): self
    {
        $this->pageSize = $size;
        return $this;
    }

    /**
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function addOrder(string $field, string $direction = self::SORT_ORDER_DESC): self
    {
        $this->orderField = $field;
        $this->orderDirection = $direction;
        return $this;
    }

    /**
     * @param $field
     * @param $condition
     * @return $this|Collection
     */
    public function addFieldToFilter($field, $condition = null): self
    {
        $this->filter[] = [
            'field' => $field,
            'condition' => $condition,
        ];

        return $this;
    }
}
