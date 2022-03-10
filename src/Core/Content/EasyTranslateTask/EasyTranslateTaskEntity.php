<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateTask;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Language\LanguageEntity;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectEntity;

class EasyTranslateTaskEntity extends Entity
{
    use EntityIdTrait;

    protected string $easyTranslateId;
    protected string $easyTranslateProjectId;
    protected string $targetLanguageId;
    protected ?EasyTranslateProjectEntity $easyTranslateProject = null;
    protected ?LanguageEntity $targetLanguage = null;
    protected string $status;

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
     * @return string
     */
    public function getEasyTranslateProjectId(): string
    {
        return $this->easyTranslateProjectId;
    }

    /**
     * @param string $easyTranslateProjectId
     */
    public function setEasyTranslateProjectId(string $easyTranslateProjectId): void
    {
        $this->easyTranslateProjectId = $easyTranslateProjectId;
    }

    /**
     * @return string
     */
    public function getTargetLanguageId(): string
    {
        return $this->targetLanguageId;
    }

    /**
     * @param string $targetLanguageId
     * @return void
     */
    public function setTargetLanguageId(string $targetLanguageId): void
    {
        $this->targetLanguageId = $targetLanguageId;
    }

    /**
     * @return EasyTranslateProjectEntity|null
     */
    public function getEasyTranslateProject(): ?EasyTranslateProjectEntity
    {
        return $this->easyTranslateProject;
    }

    /**
     * @param EasyTranslateProjectEntity|null $easyTranslateProject
     */
    public function setEasyTranslateProject(?EasyTranslateProjectEntity $easyTranslateProject): void
    {
        $this->easyTranslateProject = $easyTranslateProject;
    }

    /**
     * @return LanguageEntity|null
     */
    public function getTargetLanguage(): ?LanguageEntity
    {
        return $this->targetLanguage;
    }

    /**
     * @param LanguageEntity|null $targetLanguage
     * @return void
     */
    public function setTargetLanguage(?LanguageEntity $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
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
