<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateTask;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class EasyTranslateTaskDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'easytranslate_task';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EasyTranslateTaskEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EasyTranslateTaskCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('easytranslate_project_id', 'easyTranslateProjectId', EasyTranslateProjectDefinition::class))->addFlags(new Required()),
            (new FkField('target_language_id', 'targetLanguageId', LanguageDefinition::class))->addFlags(new Required()),
            (new StringField('easytranslate_id', 'easyTranslateId')),
            (new ManyToOneAssociationField('easyTranslateProject', 'easytranslate_project', EasyTranslateProjectDefinition::class, 'id')),
            (new ManyToOneAssociationField('targetLanguage', 'target_language', LanguageDefinition::class, 'id')),
            (new StringField('status', 'status'))->addFlags(new Required()),
        ]);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
