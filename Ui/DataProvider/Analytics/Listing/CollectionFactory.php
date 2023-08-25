<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Ui\DataProvider\Analytics\Listing;

use Magento\Framework\ObjectManagerInterface;

class  CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected string $instanceName;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(ObjectManagerInterface $objectManager, string $instanceName = Collection::class)
    {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * @param array $data
     * @return Collection
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
