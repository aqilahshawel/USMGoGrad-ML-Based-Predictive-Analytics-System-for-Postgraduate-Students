<?php

return [
    // University semester system:
    // Semester 1: October – February
    // Semester 2: March – September

    'study_modes' => [
        'full_time' => [
            'min_semesters' => 4,
            // Used for risk boundary: beyond expected but <= max => at_risk
            'expected_semesters' => 8,
            'max_semesters' => 10,

            // Default progress stages (semester numbers are relative to student start_date)
            'stages' => [
                [
                    'key' => 'prep',
                    'title' => 'Coursework / research preparation',
                    'from' => 1,
                    'to' => 2,
                    'milestone_title' => 'Complete coursework / preparation',
                ],
                [
                    'key' => 'proposal',
                    'title' => 'Proposal defense',
                    'from' => 3,
                    'to' => 4,
                    'milestone_title' => 'Proposal defense target',
                ],
                [
                    'key' => 'data_collection',
                    'title' => 'Data collection / research progress',
                    'from' => 5,
                    'to' => 6,
                    'milestone_title' => 'Data collection / progress target',
                ],
                [
                    'key' => 'thesis',
                    'title' => 'Thesis writing and submission target',
                    'from' => 7,
                    'to' => 8,
                    'milestone_title' => 'Thesis submission target',
                ],
            ],
        ],

        'part_time' => [
            'min_semesters' => 6,
            // Default assumption: part-time timelines are longer; adjust if your faculty defines otherwise.
            'expected_semesters' => 12,
            'max_semesters' => 15,

            'stages' => [
                [
                    'key' => 'prep',
                    'title' => 'Coursework / research preparation',
                    'from' => 1,
                    'to' => 3,
                    'milestone_title' => 'Complete coursework / preparation',
                ],
                [
                    'key' => 'proposal',
                    'title' => 'Proposal defense',
                    'from' => 4,
                    'to' => 6,
                    'milestone_title' => 'Proposal defense target',
                ],
                [
                    'key' => 'data_collection',
                    'title' => 'Data collection / research progress',
                    'from' => 7,
                    'to' => 9,
                    'milestone_title' => 'Data collection / progress target',
                ],
                [
                    'key' => 'thesis',
                    'title' => 'Thesis writing and submission target',
                    'from' => 10,
                    'to' => 12,
                    'milestone_title' => 'Thesis submission target',
                ],
            ],
        ],
    ],
];
