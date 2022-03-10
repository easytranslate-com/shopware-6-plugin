<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class EasyTranslateProjectProductsDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'easytranslate_project_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return new FieldCollection([
            (new FkField('easytranslate_project_id', 'easyTranslateProjectId', EasyTranslateProjectDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('easyTranslateProject', 'easytranslate_project_id', EasyTranslateProjectDefinition::class, 'id'),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
            new CreatedAtField()
        ]);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
