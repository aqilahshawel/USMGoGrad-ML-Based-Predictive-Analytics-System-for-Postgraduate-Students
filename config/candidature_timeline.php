<?php

return [
    // University semester system:
    // Semester 1: September – February
    // Semester 2: March – August

    'study_modes' => [
        'full_time' => [
            'min_semesters' => 4,
            'expected_semesters' => 8,
            'max_semesters' => 10,
            'stages' => [
                [
                    'key' => 'stage_1',
                    'title' => 'Stage 1 — Proposal & Research Review',
                    'from' => 1,
                    'to' => 5,
                ],
                [
                    'key' => 'stage_2',
                    'title' => 'Stage 2 — Submission',
                    'from' => 6,
                    'to' => 8,
                ],
            ],
            'checkpoint_targets' => [
                [
                    'key' => 'ekc500_admin_approval',
                    'title' => 'EKC500 Approval',
                    'field' => 'ekc500_admin_approved',
                    'target_semester' => 1,
                ],
                [
                    'key' => 'proposal_review',
                    'title' => 'Proposal Review Date',
                    'field' => 'proposal_review_date',
                    'target_semester' => 3,
                ],
                [
                    'key' => 'research_review',
                    'title' => 'Research Review Date',
                    'field' => 'research_review_date',
                    'target_semester' => 5,
                ],
                [
                    'key' => 'nos',
                    'title' => 'Notice of Submission Date',
                    'field' => 'nos_date',
                    'target_semester' => 7,
                ],
                [
                    'key' => 'final_draft_submission',
                    'title' => 'Final Draft Submission Date',
                    'field' => 'final_draft_submission_date',
                    'relative_to' => 'nos',
                    'offset_weeks' => 2,
                ],
                [
                    'key' => 'viva',
                    'title' => 'Viva Date',
                    'field' => 'viva_date',
                    'relative_to' => 'nos',
                    'offset_weeks' => 12,
                ],
            ],
        ],
    ],
];
