<?php

namespace App\Enums;

enum BookLanguage: string
{
    case En = 'en';
    case Ar = 'ar';
    case Fr = 'fr';

    public function label(): string
    {
        return match ($this) {
            self::En => 'English',
            self::Ar => 'Arabic',
            self::Fr => 'French',
        };
    }

    /** Translated strings used in PDF templates. */
    public function templateLabels(): array
    {
        return match ($this) {
            self::Ar => [
                'toc'            => 'جدول المحتويات',
                'chapter'        => 'الفصل',
                'chapter_label'  => 'الفصل',
                'author'         => 'المؤلف:',
                'chapters_count' => 'عدد الفصول:',
                'contents'       => 'جدول المحتويات',
            ],
            self::Fr => [
                'toc'            => 'Table des matières',
                'chapter'        => 'Chapitre',
                'chapter_label'  => 'CHAPITRE',
                'author'         => 'Auteur :',
                'chapters_count' => 'Chapitres :',
                'contents'       => 'SOMMAIRE',
            ],
            default => [
                'toc'            => 'Table of Contents',
                'chapter'        => 'Chapter',
                'chapter_label'  => 'CHAPTER',
                'author'         => 'Author:',
                'chapters_count' => 'Chapters:',
                'contents'       => 'CONTENTS',
            ],
        };
    }
}
