<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\Category;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\Aggregate\EasyTranslateProjectCategoriesDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class CategoryExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return CategoryDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'easyTranslateProjects',
                EasyTranslateProjectDefinition::class,
                EasyTranslateProjectCategoriesDefinition::class,
                'category_id',
                'easytranslate_project_id'
            ))
        );
    }
}
