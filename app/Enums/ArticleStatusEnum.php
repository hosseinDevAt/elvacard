<?php

namespace App\Enums;

enum ArticleStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'پیش‌نویس',
            self::PUBLISHED => 'منتشر شده',
        };
    }
}
