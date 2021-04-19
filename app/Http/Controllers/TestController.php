<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Interfaces\LoggerInterface;
use App\Services\PageService;
use App\Formatters\HttpDataSanitizer;

use Error;
use Exception;
use Throwable;
use App\Exceptions\MongoDB\InvalidObjectIdException;
use App\Exceptions\Pages\PageAccessDeniedException;
use App\Exceptions\Pages\PageMetaUpdateException;
use App\Exceptions\Pages\PageNotFoundException;
use App\Exceptions\Pages\PageDeleteException;
use App\Repositories\BlocksUsageLogRepository;

class TestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private PageService $pageService;
    private $logger;
    private HttpDataSanitizer $sanitizer;
    private BlocksUsageLogRepository $blocksUsageLogRepository;

    public function __construct(
        LoggerInterface $logger,
        PageService $pageService,
        HttpDataSanitizer $sanitizer,
        BlocksUsageLogRepository $blocksUsageLogRepository
    )
    {
        $this->logger = $logger;
        $this->pageService = $pageService;
        $this->sanitizer = $sanitizer;
        $this->blocksUsageLogRepository = $blocksUsageLogRepository;
    }

    public function test(): JsonResponse
    {

        $test = $this->blocksUsageLogRepository->getRecent(168143554);

        return $this->jsonResponse([
            'data' => $test
        ]);
    }
}
