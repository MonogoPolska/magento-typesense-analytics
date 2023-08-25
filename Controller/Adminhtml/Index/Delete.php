<?php
declare(strict_types=1);
namespace Monogo\TypesenseAnalytics\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use \Magento\Framework\Controller\ResultFactory;
use Monogo\TypesenseAnalytics\Services\TypesenseService;

class Delete extends Action implements HttpGetActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var TypesenseService
     */
    protected TypesenseService $typesenseService;

    /**
     * @param Context $context
     * @param TypesenseService $typesenseService
     */
    public function __construct(
        Context $context,
        TypesenseService $typesenseService
    ) {
        $this->resultFactory = $context->getResultFactory();
        $this->typesenseService = $typesenseService;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $this->typesenseService->deleteAnalyticsData();
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath('typesense_analytics/index/index');

        return $redirect;
    }
}

