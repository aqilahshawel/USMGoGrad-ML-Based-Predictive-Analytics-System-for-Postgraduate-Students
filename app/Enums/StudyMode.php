<?php

namespace App\Enums;

enum StudyMode: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';

    public static function fromMixed(string $value): self
    {
        $value = trim(strtolower($value));

        return match ($value) {
            'full-time', 'fulltime', 'full_time', 'ft' => self::FullTime,
            'part-time', 'parttime', 'part_time', 'pt' => self::PartTime,
            default => throw new \InvalidArgumentException("Invalid study_mode: {$value}"),
        };
    }
}
