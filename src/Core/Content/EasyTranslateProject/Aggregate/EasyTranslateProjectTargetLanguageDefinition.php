<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate;

use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\Language\LanguageDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class EasyTranslateProjectTargetLanguageDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'easytranslate_project_target_language';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return new FieldCollection([
            (new FkField('easytranslate_project_id', 'easyTranslateProjectId', EasyTranslateProjectDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('easyTranslateProject', 'easytranslate_project_id', EasyTranslateProjectDefinition::class, 'id'),
            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id'),
            new CreatedAtField()
        ]);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
