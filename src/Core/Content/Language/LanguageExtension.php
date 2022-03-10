<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\Language;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Wexo\EasyTranslate\Core\Content\EasyTranslateProject\EasyTranslateProjectDefinition;

class LanguageExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return LanguageDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'easyTranslateProjects',
                EasyTranslateProjectDefinition::class,
                'source_language_id'
            ))
        );
    }
}
