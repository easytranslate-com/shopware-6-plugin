<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Controller;

use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectEntity;
use Wexo\EasyTranslate\Core\Content\EasyTranslateTask\EasyTranslateTaskEntity;
use Wexo\EasyTranslate\Service\APIHelperService;
use Wexo\EasyTranslate\Service\LogService;
use Wexo\EasyTranslate\Service\TranslationHelperService;
use Wexo\EasyTranslate\WexoEasyTranslate;

/**
 * EasyTranslate Project Controller
 *
 * @package   Wexo\EasyTranslate\Controller
 *
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class EasyTranslateProjectStorefrontController extends AbstractController
{
    protected EntityRepositoryInterface $easyTranslateProjectRepository;
    protected EntityRepositoryInterface $easyTranslateTaskRepository;
    protected EntityRepositoryInterface $categoryTranslationRepository;
    protected EntityRepositoryInterface $productTranslationRepository;
    protected APIHelperService $APIHelperService;
    protected TranslationHelperService $translationHelperService;
    protected LogService $logService;

    /**
     * @param EntityRepositoryInterface $easyTranslateProjectRepository
     * @param EntityRepositoryInterface $easyTranslateTaskRepository
     * @param EntityRepositoryInterface $categoryTranslationRepository
     * @param EntityRepositoryInterface $productTranslationRepository
     * @param APIHelperService $APIHelperService
     * @param TranslationHelperService $translationHelperService
     * @param LogService $logService
     */
    public function __construct(
        EntityRepositoryInterface $easyTranslateProjectRepository,
        EntityRepositoryInterface $easyTranslateTaskRepository,
        EntityRepositoryInterface $categoryTranslationRepository,
        EntityRepositoryInterface $productTranslationRepository,
        APIHelperService $APIHelperService,
        TranslationHelperService $translationHelperService,
        LogService $logService
    ) {
        $this->easyTranslateProjectRepository = $easyTranslateProjectRepository;
        $this->easyTranslateTaskRepository = $easyTranslateTaskRepository;
        $this->categoryTranslationRepository = $categoryTranslationRepository;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->APIHelperService = $APIHelperService;
        $this->translationHelperService = $translationHelperService;
        $this->logService = $logService;
    }

    /**
     * Handle a 'task.updated' event from EasyTranslate.
     *
     * Will download the task content, update products and categories, update
     * task status, and if it's the final task, update the project status.
     *
     * @param array $data
     * @param Request $request
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    private function handleTaskUpdate(array $data, Request $request): Response
    {
        if ($data['attributes']['status'] !== WexoEasyTranslate::PROJECT_STATUS_COMPLETED) {
            $this->logService->logError(
                'Received callback with non-completed task',
                [
                    'data'    => $data,
                    'content' => $request->getContent(),
                ]
            );
            return new Response('This API currently only supports COMPLETED task updates', 400);
        }

        $taskId = $data['id'];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('easyTranslateId', $taskId));

        /** @var EasyTranslateTaskEntity $task */
        $task = $this->easyTranslateTaskRepository->search($criteria, Context::createDefaultContext())->first();
        if (!$task) {
            $this->logService->logError(
                'Received callback for missing task',
                [
                    'easyTranslateId' => $taskId,
                    'data'            => $data,
                    'content'         => $request->getContent(),
                ]
            );
            return new Response("Unable to find task with EasyTranslate id `$taskId`", 404);
        }

        if ($task->getStatus() === WexoEasyTranslate::PROJECT_STATUS_COMPLETED) {
            $this->logService->logError(
                'Received callback for already completed task',
                [
                    'task'    => $task,
                    'data'    => $data,
                    'content' => $request->getContent(),
                ]
            );
            return new Response('Task has already been completed', 400);
        }

        $projectId = $data['attributes']['project']['id'];
        $criteria = new Criteria();
        $criteria->addAssociation('easyTranslateTasks');
        $criteria->addFilter(new EqualsFilter('easyTranslateId', $projectId));

        /** @var EasyTranslateProjectEntity $project */
        $project = $this->easyTranslateProjectRepository
            ->search($criteria, Context::createDefaultContext())
            ->first();

        if (!$project) {
            $this->logService->logError(
                'Received callback for missing project',
                [
                    'easyTranslateTaskId'    => $taskId,
                    'easyTranslateProjectId' => $projectId,
                    'data'                   => $data,
                    'content'                => $request->getContent(),
                ]
            );
            return new Response("Unable to find project with EasyTranslate id `$projectId`", 404);
        }

        if ($project->getId() !== $task->getEasyTranslateProjectId()) {
            $this->logService->logError(
                'Supplied project id does not match id linked to task in Shopware',
                [
                    'easyTranslateTaskId'    => $taskId,
                    'easyTranslateProjectId' => $projectId,
                    'project'                => $project,
                    'task'                   => $task,
                    'data'                   => $data,
                    'content'                => $request->getContent(),
                ]
            );
            return new Response("Supplied project id does not match id linked to task in Shopware", 404);
        }

        $targetContentURL = $data['attributes']['target_content'];
        $content = $this->APIHelperService->downloadTaskContent($targetContentURL);

        $this->upsertCategories($content, $task);
        $this->upsertProducts($content, $task);

        $this->easyTranslateTaskRepository->update([
            [
                'id' => $task->getId(),
                'status' => WexoEasyTranslate::PROJECT_STATUS_COMPLETED,
            ]
        ], Context::createDefaultContext());

        $completedTasksCount = $project
            ->getEasyTranslateTasks()
            ->filterByProperty('status', WexoEasyTranslate::PROJECT_STATUS_COMPLETED)
            ->count();

        if ($project->getEasyTranslateTasks()->count() == $completedTasksCount + 1) {
            $this->easyTranslateProjectRepository->update([
                [
                    'id' => $project->getId(),
                    'status' => WexoEasyTranslate::PROJECT_STATUS_COMPLETED,
                ]
            ], Context::createDefaultContext());
        }

        return new Response('Success', 200);
    }

    /**
     * Handle a 'project.status.approval_needed' callback from EasyTranslate.
     *
     * Updates the price for the project and sets the status to 'APPROVAL_NEEDED'.
     *
     * @param array $data
     * @param Request $request
     * @return Response
     */
    private function handleProjectApproval(array $data, Request $request): Response
    {
        $projectId = $data['id'];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('easyTranslateId', $projectId));

        /** @var EasyTranslateProjectEntity $project */
        $project = $this->easyTranslateProjectRepository
            ->search($criteria, Context::createDefaultContext())
            ->first();

        if (!$project) {
            $this->logService->logError(
                'Received callback for missing project',
                [
                    'easyTranslateId' => $projectId,
                    'data'            => $data,
                    'content'         => $request->getContent(),
                ]
            );
            return new Response("Unable to find project with EasyTranslate id `$projectId`", 404);
        }

        // Price is sent in cents, so needs to be divided by 100
        $price = (float)$data['attributes']['price']['amount_euro'] / 100;

        // TODO: Does currency need to be considered?
        $this->easyTranslateProjectRepository->update([
            [
                'id' => $project->getId(),
                'status' => WexoEasyTranslate::PROJECT_STATUS_APPROVAL_NEEDED,
                'translationPrice' => $price,
            ]
        ], Context::createDefaultContext());

        return new Response('Success', 200);
    }

    /**
     * Handle project status events, such as price accepted or declined.
     *
     * Will update the price and project on the status based on the callback.
     *
     * @param array $data
     * @param Request $request
     * @param string $status
     * @return Response
     */
    private function handleProjectStatusUpdate(array $data, Request $request, string $status): Response
    {
        $projectId = $data['id'];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('easyTranslateId', $projectId));

        /** @var EasyTranslateProjectEntity $project */
        $project = $this->easyTranslateProjectRepository
            ->search($criteria, Context::createDefaultContext())
            ->first();

        if (!$project) {
            $this->logService->logError(
                'Received callback for missing project',
                [
                    'easyTranslateId' => $projectId,
                    'data'            => $data,
                    'content'         => $request->getContent(),
                ]
            );
            return new Response("Unable to find project with EasyTranslate id `$projectId`", 404);
        }

        // Price is sent in cents, so needs to be divided by 100
        $price = (float)$data['attributes']['price']['amount_euro'] / 100;

        if (!($project->getStatus() == $status)) {
            $updateData = [
                'id' => $project->getId(),
                'status' => $status,
            ];

            if ($price > 0) {
                $updateData['translationPrice'] = $price;
            }

            $this->easyTranslateProjectRepository->update([$updateData], Context::createDefaultContext());
        }

        return new Response('Success', 200);
    }

    /**
     * @Route(
     *     "/store-api/easytranslate/project/callback",
     *     name="store-api.easytranslate.project.callback",
     *     methods={"POST"},
     *     defaults={"auth_required"=false}
     * )
     */
    public function projectCallback(Request $request): Response
    {
        $event = $request->get('event');
        if (!$event) {
            $this->logService->logError(
                'Missing `event` in callback',
                [
                    'content' => $request->getContent(),
                ]
            );
            return new Response('Missing `event` in request', 400);
        }

        $data = $request->get('data');
        if (!$data) {
            $this->logService->logError(
                'Missing `data` in callback',
                [
                    'content' => $request->getContent(),
                ]
            );
            return new Response('Missing `data` in request', 400);
        }

        switch ($event) {
            case WexoEasyTranslate::EVENT_TASK_UPDATE:
                return $this->handleTaskUpdate($data, $request);
            case WexoEasyTranslate::EVENT_PROJECT_STATUS_APPROVAL:
                return $this->handleProjectApproval($data, $request);
            case WexoEasyTranslate::EVENT_PROJECT_STATUS_ACCEPTED:
                return $this->handleProjectStatusUpdate(
                    $data,
                    $request,
                    WexoEasyTranslate::PROJECT_STATUS_APPROVED
                );
            case WexoEasyTranslate::EVENT_PROJECT_STATUS_DECLINED:
                return $this->handleProjectStatusUpdate(
                    $data,
                    $request,
                    WexoEasyTranslate::PROJECT_STATUS_DECLINED
                );
            default:
                return new Response("Unrecognized event `$event`", 400);
        }
    }

    public function upsertCategories($content, $task)
    {
        if (array_key_exists('categories', $content)) {
            $categoriesUpdateArray = $this->translationHelperService->makeTranslationUpdateArray(
                'category',
                $content['categories'],
                $task->getTargetLanguageId()
            );
            $this->categoryTranslationRepository->upsert($categoriesUpdateArray, Context::createDefaultContext());
        }
    }

    public function upsertProducts($content, $task)
    {
        if (array_key_exists('products', $content)) {
            $productsUpdateArray = $this->translationHelperService->makeTranslationUpdateArray(
                'product',
                $content['products'],
                $task->getTargetLanguageId()
            );

            $this->productTranslationRepository->upsert($productsUpdateArray, Context::createDefaultContext());
        }
    }
}
