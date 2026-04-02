<?php

namespace App\Enums;

enum StudyMode: string
{
    case FullTime = 'full_time';

    public static function fromMixed(?string $value): self
    {
        return self::FullTime;
    }
}
