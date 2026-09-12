<?php

namespace App\Enums;

enum HomepageSectionTypeEnum: string
{
    case HERO = 'hero';
    case BANNER = 'banner';
    case FEATURED_PRODUCTS = 'featured_products';
    case FEATURED_DESIGNS = 'featured_designs';
    case FAQ = 'faq';
    case TEXT_BLOCK = 'text_block';
    case NEWEST_PRODUCTS = 'newest_products';
    case ARTICLES = 'articles';

    public function label(): string
    {
        return str_replace('_', ' ', ucfirst($this->value));
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::HERO => 'بنر اصلی',
            self::BANNER => 'بنر',
            self::FEATURED_PRODUCTS => 'محصولات ویژه',
            self::FEATURED_DESIGNS => 'طرح‌های ویژه',
            self::FAQ => 'سوالات متداول',
            self::TEXT_BLOCK => 'بلاک متنی',
            self::NEWEST_PRODUCTS => 'جدیدترین محصولات',
            self::ARTICLES => 'مقالات',
        };
    }
}
