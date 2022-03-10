<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\Product;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate\EasyTranslateProjectProductsDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class ProductExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'easyTranslateProjects',
                EasyTranslateProjectDefinition::class,
                EasyTranslateProjectProductsDefinition::class,
                'product_id',
                'easytranslate_project_id'
            ))
        );
    }
}
