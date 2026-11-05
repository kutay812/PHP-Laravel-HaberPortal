<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Taslak',
            self::Pending => 'Onay Bekliyor',
            self::Published => 'Yayında',
            self::Archived => 'Arşivlendi',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

