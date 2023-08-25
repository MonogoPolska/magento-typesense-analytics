<?php
declare(strict_types=1);

namespace Monogo\TypesenseAnalytics\Model\Config\Source;

use Monogo\TypesenseCore\Model\Indexer\RegisteredIndexers as SourceIndexers;

class RegisteredIndexers
{
    protected SourceIndexers $sourceIndexers;

    /**
     * @param SourceIndexers $sourceIndexers
     */
    public function __construct(SourceIndexers $sourceIndexers)
    {
        $this->sourceIndexers = $sourceIndexers;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $indexers = $this->sourceIndexers->getAdditionalData();

        $options = [];
        foreach ($indexers as $value) {
            $options[] = [
                'value' => $value['name'],
                'label' => __($value['label']),
            ];
        }
        return $options;
    }
}
