<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Services;

use Http\Client\Exception;
use Monogo\TypesenseCore\Adapter\Client;
use Monogo\TypesenseCore\Exceptions\ConnectionException;
use Monogo\TypesenseCore\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Typesense\Client as TypeSenseClient;
use Typesense\Collection;
use Typesense\Exceptions\ConfigError;
use Typesense\Exceptions\TypesenseClientError;

class TypesenseService
{
    /**
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * @var TypeSenseClient|null
     */
    protected ?TypeSenseClient $client;

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var Logger
     */
    protected Logger $logService;

    /**
     * @var array
     */
    protected array $indexes = [];

    /**
     * @param ConfigService $configService
     * @param Client $client
     * @param Logger $logService
     * @throws ConfigError
     * @throws ConnectionException
     * @throws Exception
     */
    public function __construct(
        ConfigService $configService,
        Client        $client,
        Logger        $logService
    )
    {
        $this->configService = $configService;
        $this->client = $client->getClient();
        $this->logService = $logService;
    }

    /**
     * @param string $name
     * @return Collection
     * @throws Exception
     * @throws TypesenseClientError
     */
    public function getIndex(string $name): Collection
    {
        if (!isset($this->indexes[$name])) {
            $collections = $this->getCollectionTable();

            if (key_exists($name, $collections)) {
                $index = $this->client->getCollections()->offsetGet($name);
                try {
                    $index->update($this->getIndexSchema($name));
                } catch (\Exception $e) {
                    $this->logService->alert('Index update error: ' . $e->getMessage());
                }
                $this->indexes[$name] = $index;
            } else {

                $this->client->getCollections()->create($this->getIndexSchema($name));
                $this->indexes[$name] = $this->client->getCollections()->offsetGet($name);
            }
        }
        return $this->indexes[$name];
    }

    /**
     * @param string $analyticsIndex
     * @param string $collectionName
     * @return void
     * @throws Exception
     * @throws TypesenseClientError
     */
    public function setAnalyticsRule(string $analyticsIndex, string $collectionName): void
    {
        $this->getIndex($analyticsIndex);
        $ruleName = $analyticsIndex . '_rule';
        $ruleConfiguration = [
            'type' => 'popular_queries',
            'params' => [
                'source' => [
                    'collections' => [$collectionName]
                ],
                'destination' => [
                    'collection' => $analyticsIndex
                ],
                'limit' => $this->configService->getQueriesLimit(),
            ]
        ];
        $this->client->getAnalytics()->rules()->upsert($ruleName, $ruleConfiguration);
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    public function deleteAnalyticsRules(OutputInterface $output): void
    {
        try {
            $rules = $this->client->getAnalytics()->rules()->retrieve();
            if (!empty($rules) && is_array($rules) && isset($rules['rules']) && count($rules['rules'])) {
                $output->writeln('Removing analytic rules');
                foreach ($rules['rules'] as $rule) {
                    if (isset($rule['name'])) {
                        $ruleObject = $this->client->getAnalytics()->rules()->__get($rule['name']);
                        if (isset($ruleObject)) {
                            $ruleObject->delete();
                            $output->writeln($rule['name']);

                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $name
     * @return array
     */
    public function getIndexSchema(string $name): array
    {
        return [
            'name' => $name,
            'fields' => [
                ['name' => 'q', 'type' => 'string', 'sort' => true, 'index' => true],
                ['name' => 'count', 'type' => 'int32', 'sort' => true, 'index' => true],
            ],
        ];
    }

    /**
     * @param string $index
     * @return string|null
     */
    public function getAggregationIndexName(string $index): ?string
    {
        return $index . $this->configService->getAggregationSuffix();
    }

    /**
     * @param int|null $storeId
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAggregationCollections(int $storeId = null): ?array
    {
        $collection = [];
        $indexes = $this->configService->getSelectedIndexes($storeId);
        foreach ($indexes as $index) {
            if (str_contains($index, 'admin')) {
                continue;
            }
            $collectionTsName = $this->getAggregationIndexName($index);

            $search = [$this->configService->getIndexPrefix(), $this->configService->getAggregationSuffix()];
            $replace = ['', ''];

            $collectionName = str_replace($search, $replace, $collectionTsName);
            $collectionName = str_replace('_', ' ', ucwords($collectionName, '_'));

            $collection[$collectionTsName] = $collectionName;
        }
        return $collection;
    }


    /**
     * @return array
     * @throws Exception
     * @throws TypesenseClientError
     */
    public function getCollectionTable(): array
    {
        $collectionTable = [];
        $collections = $this->getCollections();
        foreach ($collections as $collection) {
            $collectionTable[$collection['name']] = 1;
        }
        return $collectionTable;
    }


    /**
     * @return array
     * @throws Exception
     * @throws TypesenseClientError
     */
    public function getCollections(): array
    {
        return $this->client->getCollections()->retrieve();
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     * @throws TypesenseClientError
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAnalyticsData(array $params): array
    {
        $analyticsData = [];
        $indexFilter = [];
        $queryFilter = [];
        $filters = $params['filter'];

        foreach ($filters as $filter) {
            if ($filter['field'] == 'index') {
                $indexFilter = $filter['condition']['in'];
            } elseif ($filter['field'] == 'q') {
                $queryFilter[] = str_replace('%', '', $filter['condition']['like']);
            }
        }
        $indexes = $this->getAggregationCollections();
        $searches = [];
        foreach ($indexes as $indexName => $index) {

            if (!empty($indexFilter) && !in_array($indexName, $indexFilter)) {
                continue;
            }

            $searches[] = [
                'collection' => $indexName,
                'q' => '*',
            ];
        }

        $searchRequests = [
            'searches' => $searches
        ];

        $commonSearchParams = [
            'page' => $params['page'] ?? 1,
            'per_page' => $params['page_size'] ?? 10,
            'sort_by' => $params['order_by'] . ":" . strtolower($params['order_direction'] ?? 'ASC'),
        ];
        if (!empty($queryFilter)) {
            $commonSearchParams['filter_by'] = 'q: [' . implode(',', $queryFilter) . ']';
        }


        $results = $this->client->getMultiSearch()->perform($searchRequests, $commonSearchParams);

        if (!isset($results['results'])) {
            return [];
        }
        foreach ($results['results'] as $result) {
            if (!isset($result['request_params'])) {
                continue;
            }
            $index = $result['request_params']['collection_name'];
            foreach ($result['hits'] as $item) {

                $analyticsData[] = [
                    'index_id' => $item['document']['id'],
                    'q' => $item['document']['q'],
                    'count' => $item['document']['count'],
                    'index' => $index,
                    'index_name' => $indexes[$index],
                ];
            }
        }

        return $analyticsData;
    }

    public function deleteAnalyticsData(): void
    {
        $collections = $this->client->collections->retrieve();

        foreach ($collections as $collection) {
            if (str_contains($collection['name'], $this->configService->getAggregationSuffix())) {
                try {
                    $this->client->collections[$collection['name']]->documents->delete(['filter_by' => 'count:>0']);
                } catch (\Exception $e) {
                }
            }

        }
    }
}
