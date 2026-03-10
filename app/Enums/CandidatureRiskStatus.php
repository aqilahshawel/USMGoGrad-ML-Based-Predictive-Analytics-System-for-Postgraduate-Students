<?php

namespace App\Enums;

enum CandidatureRiskStatus: string
{
    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Overdue = 'overdue';
}
