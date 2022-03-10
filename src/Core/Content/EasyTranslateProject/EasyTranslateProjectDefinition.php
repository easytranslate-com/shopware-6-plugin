<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateProject;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateTask\EasyTranslateTaskDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate\EasyTranslateProjectCategoriesDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate\EasyTranslateProjectProductsDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate\EasyTranslateProjectTargetLanguageDefinition;

class EasyTranslateProjectDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'easytranslate_project';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EasyTranslateProjectEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EasyTranslateProjectCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('source_language_id', 'sourceLanguageId', LanguageDefinition::class))->addFlags(new Required()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('workflow', 'workflow'))->addFlags(new Required()),
            (new StringField('easytranslate_id', 'easyTranslateId')),
            (new FloatField('translation_price', 'translationPrice')),
            (new OneToManyAssociationField('easyTranslateTasks', EasyTranslateTaskDefinition::class, 'easytranslate_project_id'))->addFlags(new CascadeDelete()),
            (new ManyToOneAssociationField('sourceLanguage', 'source_language_id', LanguageDefinition::class, 'id')),
            (new ManyToManyAssociationField('categories', CategoryDefinition::class, EasyTranslateProjectCategoriesDefinition::class, 'easytranslate_project_id', 'category_id')),
            (new ManyToManyAssociationField('products', ProductDefinition::class, EasyTranslateProjectProductsDefinition::class, 'easytranslate_project_id', 'product_id')),
            (new ManyToManyAssociationField('targetLanguages', LanguageDefinition::class, EasyTranslateProjectTargetLanguageDefinition::class, 'easytranslate_project_id', 'language_id')),
            (new StringField('status', 'status'))->addFlags(new Required())
        ]);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
