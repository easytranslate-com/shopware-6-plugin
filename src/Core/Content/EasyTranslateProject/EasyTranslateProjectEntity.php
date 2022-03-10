<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateProject;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Wexo\EasyTranslate\Core\Content\EasyTranslateTask\EasyTranslateTaskCollection;

class EasyTranslateProjectEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;
    protected string $workflow;
    protected string $easyTranslateId;
    protected float $translationPrice;
    protected ?EasyTranslateTaskCollection $easyTranslateTasks = null;
    protected string $sourceLanguageId;
    protected ?LanguageEntity $sourceLanguage = null;
    protected ?CategoryCollection $categories = null;
    protected ?ProductCollection $products = null;
    protected ?LanguageCollection $targetLanguages = null;
    protected string $status;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    /**
     * @param string $workflow
     * @return void
     */
    public function setWorkflow(string $workflow): void
    {
        $this->workflow = $workflow;
    }

    /**
     * @return string
     */
    public function getEasyTranslateId(): string
    {
        return $this->easyTranslateId;
    }

    /**
     * @param string $easyTranslateId
     */
    public function setEasyTranslateId(string $easyTranslateId): void
    {
        $this->easyTranslateId = $easyTranslateId;
    }

    /**
     * @return float
     */
    public function getTranslationPrice(): float
    {
        return $this->translationPrice;
    }

    /**
     * @param float $translationPrice
     */
    public function setTranslationPrice(float $translationPrice): void
    {
        $this->translationPrice = $translationPrice;
    }

    /**
     * @return EasyTranslateTaskCollection|null
     */
    public function getEasyTranslateTasks(): ?EasyTranslateTaskCollection
    {
        return $this->easyTranslateTasks;
    }

    /**
     * @param EasyTranslateTaskCollection|null $easyTranslateTasks
     * @return void
     */
    public function setEasyTranslateTasks(?EasyTranslateTaskCollection $easyTranslateTasks): void
    {
        $this->easyTranslateTasks = $easyTranslateTasks;
    }

    /**
     * @return string
     */
    public function getSourceLanguageId(): string
    {
        return $this->sourceLanguageId;
    }

    /**
     * @param string $sourceLanguageId
     * @return void
     */
    public function setSourceLanguageId(string $sourceLanguageId): void
    {
        $this->sourceLanguageId = $sourceLanguageId;
    }

    /**
     * @return LanguageEntity|null
     */
    public function getSourceLanguage(): ?LanguageEntity
    {
        return $this->sourceLanguage;
    }

    /**
     * @param LanguageEntity|null $sourceLanguage
     * @return void
     */
    public function setSourceLanguage(?LanguageEntity $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * @return CategoryCollection|null
     */
    public function getCategories(): ?CategoryCollection
    {
        return $this->categories;
    }

    /**
     * @param CategoryCollection|null $categories
     * @return void
     */
    public function setCategories(?CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return ProductCollection|null
     */
    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    /**
     * @param ProductCollection|null $products
     * @return void
     */
    public function setProducts(?ProductCollection $products): void
    {
        $this->products = $products;
    }

    /**
     * @return LanguageCollection|null
     */
    public function getTargetLanguages(): ?LanguageCollection
    {
        return $this->targetLanguages;
    }

    /**
     * @param LanguageCollection|null $targetLanguages
     * @return void
     */
    public function setTargetLanguages(?LanguageCollection $targetLanguages): void
    {
        $this->targetLanguages = $targetLanguages;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
