<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateProject;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class EasyTranslateProjectCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EasyTranslateProjectEntity::class;
    }
}
