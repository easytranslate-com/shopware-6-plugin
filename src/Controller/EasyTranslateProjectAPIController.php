<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectEntity;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Wexo\EasyTranslate\Service\APIHelperService;
use Wexo\EasyTranslate\Service\TranslationHelperService;
use Wexo\EasyTranslate\WexoEasyTranslate;

/**
 * EasyTranslate Project Controller
 *
 * @package   Wexo\EasyTranslate\Controller
 *
 * @RouteScope(scopes={"api"})
 */
class EasyTranslateProjectAPIController extends AbstractController
{
    private EntityRepositoryInterface $easyTranslateProjectRepository;
    private APIHelperService $APIHelperService;
    private TranslationHelperService $translationHelperService;

    /**
     * @param EntityRepositoryInterface $easyTranslateProjectRepository
     * @param APIHelperService $APIHelperService
     * @param TranslationHelperService $translationHelperService
     */
    public function __construct(
        EntityRepositoryInterface $easyTranslateProjectRepository,
        APIHelperService $APIHelperService,
        TranslationHelperService $translationHelperService
    ) {
        $this->easyTranslateProjectRepository = $easyTranslateProjectRepository;
        $this->APIHelperService = $APIHelperService;
        $this->translationHelperService = $translationHelperService;
    }

    /**
     * @Route(
     *     "/api/_action/easytranslate/project/sendProject",
     *     name="api.action.easytranslate.project",
     *     options={"seo"="false"},
     *     methods={"POST"},
     * )
     */
    public function startEasyTranslateProject(Request $request, Context $context): Response
    {
        $projectId = $request->get('projectId');
        if (!$projectId) {
            return new Response('Missing `projectId` from request', 400);
        }

        $criteria = new Criteria([$projectId]);
        $criteria->addAssociation('sourceLanguage.locale');
        $criteria->addAssociation('categories.translations.slotConfig');
        $criteria->addAssociation('categories.cmsPage.sections.blocks.slots.translations');
        $criteria->addAssociation('products.translations.slotConfig');
        $criteria->addAssociation('products.cmsPage.sections.blocks.slots.translations');
        $criteria->addAssociation('targetLanguages.locale');

        /** @var EasyTranslateProjectEntity $project */
        $project = $this->easyTranslateProjectRepository->search($criteria, $context)->first();
        if (!$project) {
            return new Response("Unable to find project with id `$projectId`", 404);
        }

        if ($project->getStatus() !== WexoEasyTranslate::PROJECT_STATUS_INIT) {
            return new Response(
                "Project with id `$projectId` has the wrong status. " .
                "It is `{$project->getStatus()}` but should be " .
                WexoEasyTranslate::PROJECT_STATUS_INIT . '.',
                400
            );
        }

        // Get languages from EasyTranslate
        $languageCodes = $this->APIHelperService->getApiSettings()['data'][0];

        $sourceLanguageCode = $project->getSourceLanguage()->getLocale()->getCode();

        $noUnderscoreCode = str_replace('-', '_', $sourceLanguageCode);
        $firstTwoCode = substr($sourceLanguageCode, 0, 2);

        $sources = $languageCodes['attributes']['source'];

        $sourceMatch = null;
        if (array_key_exists($noUnderscoreCode, $sources)) {
            $sourceMatch = $noUnderscoreCode;
        } elseif (array_key_exists($firstTwoCode, $sources)) {
            $sourceMatch = $firstTwoCode;
        }

        if (!$sourceMatch) {
            return new Response('Unable to find a matching source language code at EasyTranslate', 400);
        }

        $targets = $languageCodes['attributes']['target'];

        $targetLanguages = $project->getTargetLanguages();
        $targetMatches = [];
        foreach ($targetLanguages as $targetLanguage) {
            $targetLanguageLocale = $targetLanguage->getLocale();
            $code = $targetLanguageLocale->getCode();

            $noUnderscoreCode = str_replace('-', '_', $code);
            $firstTwoCode = substr($code, 0, 2);

            $targetMatch = null;
            if (array_key_exists($noUnderscoreCode, $targets)) {
                $targetMatch = $noUnderscoreCode;
            } elseif (array_key_exists($firstTwoCode, $targets)) {
                $targetMatch = $firstTwoCode;
            }

            if (!$targetMatch) {
                return new Response("Unable to find a matching target language for `$code` at EasyTranslate", 400);
            }
            $targetMatches[$targetMatch] = $targetLanguage->getId();
        }

        $content = [];
        $categoriesContent = $this->translationHelperService->getCategoryCollectionTranslatedContent(
            $project->getCategories(),
            $project->getSourceLanguageId()
        );
        if (!empty($categoriesContent)) {
            $content['categories'] = $categoriesContent;
        }

        $productsContent = $this->translationHelperService->getProductCollectionTranslatedContent(
            $project->getProducts(),
            $project->getSourceLanguageId()
        );
        if (!empty($productsContent)) {
            $content['products'] = $productsContent;
        }

        if (empty($content)) {
            return new Response('No content available for translation', 400);
        }

        $requestData = [
            'type' => 'projects',
            'attributes' => [
                'name' => $project->getName(),
                'source_language' => $sourceMatch,
                'target_languages' => array_keys($targetMatches),
                'callback_url' => $this->generateUrl('store-api.easytranslate.project.callback', [], 0),
                'workflow_id' => $project->getWorkflow(),
                'content' => $content
            ]
        ];

        $res = $this->APIHelperService->createNewProject($requestData);

        $status = $res['data']['attributes']['status'];
        $easyTranslateId = $res['data']['id'];

        $tasks = [];
        foreach ($res['data']['attributes']['tasks'] as $task) {
            if ($task['type'] !== 'task') {
                continue;
            }

            $targetLanguage = $task['attributes']['target_language'];
            $targetLanguageId = $targetMatches[$targetLanguage];

            $tasks[] = [
                'id' => Uuid::randomHex(),
                'easyTranslateProjectId' => $project->getId(),
                'targetLanguageId' => $targetLanguageId,
                'easyTranslateId' => $task['id'],
                'status' => $task['attributes']['status'],
            ];
        }

        $this->easyTranslateProjectRepository->update([
            [
                'id' => $project->getId(),
                'easyTranslateId' => $easyTranslateId,
                'status' => $status,
                'easyTranslateTasks' => $tasks,
            ]
        ], $context);

        return new Response('Success', 200);
    }

    /**
     * @Route(
     *     "/api/_action/easytranslate/project/handlePrice",
     *     name="api.action.easytranslate.project.handle.price",
     *     options={"seo"="false"},
     *     methods={"POST"},
     * )
     */
    public function handlePrice(Request $request, Context $context): Response
    {
        $projectId = $request->get('projectId');
        if (!$projectId) {
            return new Response('Missing `projectId` from request', 400);
        }

        $action = $request->get('action');
        if (!$action) {
            return new Response('Missing `action` from request', 400);
        }

        if ($action != WexoEasyTranslate::PROJECT_STATUS_APPROVED
            && $action != WexoEasyTranslate::PROJECT_STATUS_DECLINED) {
            return new Response(
                "Action `$action` not recognized. " .
                "Should be one of " . WexoEasyTranslate::PROJECT_STATUS_APPROVED .
                ', ' . WexoEasyTranslate::PROJECT_STATUS_DECLINED,
                400
            );
        }

        $criteria = new Criteria([$projectId]);

        /** @var EasyTranslateProjectEntity $project */
        $project = $this->easyTranslateProjectRepository->search($criteria, $context)->first();
        if (!$project) {
            return new Response("Unable to find project with id `$projectId`", 404);
        }

        $this->APIHelperService->handlePriceApproval(
            $project->getEasyTranslateId(),
            $action == WexoEasyTranslate::PROJECT_STATUS_APPROVED
        );

        $this->easyTranslateProjectRepository->update([
            [
                'id' => $project->getId(),
                'status' => $action
            ]
        ], $context);

        return new Response('Success', 200);
    }
}
