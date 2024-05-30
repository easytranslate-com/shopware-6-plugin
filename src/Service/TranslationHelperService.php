<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Service;

use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;

/**
 *
 */
class TranslationHelperService
{
    /**
     * Get content to be translated from a Category or Product.
     *
     * @param CategoryEntity|ProductEntity $entity
     * @param CategoryTranslationEntity|ProductTranslationEntity $translation
     * @param string $languageId
     * @return array
     */
    protected function getTranslationSlotContent($entity, $translation, string $languageId): array
    {
        $slotContent = [];
        if (!empty($translation->getSlotConfig())) {
            foreach ($translation->getSlotConfig() as $slotId => $config) {
                if (!array_key_exists('content', $config) || !array_key_exists('value', $config['content'])) {
                    continue;
                }

                if (array_key_exists('source', $config['content']) && $config['content']['source'] === 'mapped') {
                    continue;
                }

                $slotContent[$slotId] = $config['content']['value'];
            }
        }

        if ($entity->getCmsPage() && $entity->getCmsPage()->getSections()) {
            $slots = $entity
                ->getCmsPage()
                ->getSections()
                ->getBlocks()
                ->getSlots()
                ->filterByProperty('type', 'text')
                ->getElements();

            /**
             * @var string $slotId
             * @var CmsSlotEntity $slot
             */
            foreach ($slots as $slotId => $slot) {
                if (array_key_exists($slotId, $slotContent)) {
                    continue;
                }

                /** @var CmsSlotTranslationEntity $slotTranslation */
                $slotTranslation = $slot->getTranslations()->filterByProperty('languageId', $languageId)->first();
                if (!$slotTranslation) {
                    continue;
                }
                $config = $slotTranslation->getConfig();

                if (!array_key_exists('content', $config) || !array_key_exists('value', $config['content'])) {
                    continue;
                }

                if (array_key_exists('source', $config['content']) && $config['content']['source'] === 'mapped') {
                    continue;
                }

                $slotContent[$slotId] = $config['content']['value'];
            }
        }

        return $slotContent;
    }

    /**
     * Get content to be translated from a CategoryCollection and language.
     *
     * @param CategoryCollection $collection
     * @param string $languageId
     * @return array
     */
    public function getCategoryCollectionTranslatedContent(CategoryCollection $collection, string $languageId): array
    {
        $content = [];
        foreach ($collection as $category) {
            $translations = $category->getTranslations();
            if (!$translations) {
                continue;
            }

            $translation = $translations->filterByLanguageId($languageId)->first();
            if (!$translation) {
                continue;
            }

            $translationContent = [];
            if ($translation->getName()) {
                $translationContent['name'] = $translation->getName();
            }

            if ($translation->getDescription()) {
                $translationContent['description'] = $translation->getDescription();
            }

            $slotContent = $this->getTranslationSlotContent($category, $translation, $languageId);
            if (!empty($slotContent)) {
                $translationContent['slotConfig'] = $slotContent;
            }

            if (!empty($translationContent)) {
                $id = $category->getId() . '-' . $category->getVersionId();
                $content[$id] = $translationContent;
            }
        }

        return $content;
    }

    /**
     * Convert data in EasyTranslate translation to array usable for an update or upsert.
     *
     * @param string $type
     * @param array $content
     * @param string $languageId
     * @return array
     */
    public function makeTranslationUpdateArray(string $type, array $content, string $languageId): array
    {
        $update = [];
        foreach ($content as $id => $translation) {
            $arr = $translation;

            $splitId = explode('-', $id);
            $arr[$type . 'Id'] = $splitId[0];
            $arr[$type . 'VersionId'] = $splitId[1];
            $arr['languageId'] = $languageId;

            if (array_key_exists('slotConfig', $arr)) {
                $arr['slotConfig'] = array_map(function ($slot) {
                    return [
                        'content' => [
                            'value' => $slot,
                        ],
                    ];
                }, $arr['slotConfig']);
            }

            $update[] = $arr;
        }

        return $update;
    }

    /**
     * Get content to be translated from a ProductCollection and language.
     *
     * @param ProductCollection $collection
     * @param string $languageId
     * @return array
     */
    public function getProductCollectionTranslatedContent(ProductCollection $collection, string $languageId): array
    {
        $content = [];
        foreach ($collection as $product) {
            $translations = $product->getTranslations();
            if (!$translations) {
                continue;
            }

            $translation = $translations->filterByLanguageId($languageId)->first();
            if (!$translation) {
                continue;
            }

            $translationContent = [];
            if ($translation->getName()) {
                $translationContent['name'] = $translation->getName();
            }

            if ($translation->getDescription()) {
                $translationContent['description'] = $translation->getDescription();
            }

            if ($translation->getMetaTitle()) {
                $translationContent['metaTitle'] = $translation->getMetaTitle();
            }

            if ($translation->getMetaDescription()) {
                $translationContent['metaDescription'] = $translation->getMetaDescription();
            }

            if ($translation->getKeywords()) {
                $translationContent['keywords'] = $translation->getKeywords();
            }

            $slotContent = $this->getTranslationSlotContent($product, $translation, $languageId);
            if (!empty($slotContent)) {
                $translationContent['slotConfig'] = $slotContent;
            }

            if (!empty($translationContent)) {
                $id = $product->getId() . '-' . $product->getVersionId();
                $content[$id] = $translationContent;
            }
        }

        return $content;
    }
}
