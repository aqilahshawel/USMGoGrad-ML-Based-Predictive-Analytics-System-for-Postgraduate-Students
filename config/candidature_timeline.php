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
                    'key' => 'ekc500_and_proposal',
                    'title' => 'EKC500 approval and proposal preparation',
                    'from' => 1,
                    'to' => 3,
                    'milestone_title' => 'Proposal review target',
                ],
                [
                    'key' => 'research_review_window',
                    'title' => 'Research review progression',
                    'from' => 4,
                    'to' => 5,
                    'milestone_title' => 'Research review target',
                ],
                [
                    'key' => 'submission_window',
                    'title' => 'Submission and viva preparation',
                    'from' => 6,
                    'to' => 8,
                    'milestone_title' => 'Notice of submission target',
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
        'part_time' => [
            'min_semesters' => 6,
            'expected_semesters' => 12,
            'max_semesters' => 15,
            'stages' => [
                [
                    'key' => 'ekc500_and_proposal',
                    'title' => 'EKC500 approval and proposal preparation',
                    'from' => 1,
                    'to' => 3,
                    'milestone_title' => 'Proposal review target',
                ],
                [
                    'key' => 'research_review_window',
                    'title' => 'Research review progression',
                    'from' => 4,
                    'to' => 7,
                    'milestone_title' => 'Research review target',
                ],
                [
                    'key' => 'submission_window',
                    'title' => 'Submission and viva preparation',
                    'from' => 8,
                    'to' => 12,
                    'milestone_title' => 'Notice of submission target',
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
                    'target_semester' => 7,
                ],
                [
                    'key' => 'nos',
                    'title' => 'Notice of Submission Date',
                    'field' => 'nos_date',
                    'target_semester' => 11,
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
