<?php

namespace App\Enums;

enum StudyMode: string
{
    case FullTime = 'full_time';

    public static function fromMixed(?string $value): self
    {
        if ($value === null) {
            return self::FullTime;
        }

        $value = trim(strtolower($value));

        return match ($value) {
            '', 'full-time', 'fulltime', 'full_time', 'full time', 'ft',
            'part-time', 'parttime', 'part_time', 'part time', 'pt' => self::FullTime,
            default => self::FullTime,
        };
    }
}
