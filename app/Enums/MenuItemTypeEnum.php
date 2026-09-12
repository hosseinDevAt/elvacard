<?php

namespace App\Enums;

enum MenuItemTypeEnum: string
{
    case URL = 'url';
    case PAGE = 'page';
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case DESIGN = 'design';
    case ARTICLE = 'article';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
