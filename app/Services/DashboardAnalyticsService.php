<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudentCheckpoint;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use SplFileObject;

class DashboardAnalyticsService
{
    private ?array $historicalDatasetCache = null;

    /**
     * Get graduation success rates grouped by cluster
     * Uses the historical CSV dataset for trend analysis.
     */
    public function getClusterStatistics(): array
    {
        $rows = $this->historicalDatasetRows();
        $clusterGroups = collect($rows)->groupBy('cluster');
        $clusterStats = [];

        foreach ($clusterGroups as $cluster => $clusterStudents) {
            $total = $clusterStudents->count();
            
            // Count students who graduated on time (<= 42 months / <= 7 semesters)
            $onTimeGraduations = $clusterStudents
                ->filter(function (array $row) {
                    if (empty($row['registration_date']) || empty($row['viva_date'])) {
                        return false;
                    }
                    $startDate = Carbon::parse($row['registration_date']);
                    $vivaDate = Carbon::parse($row['viva_date']);
                    $monthsTaken = $startDate->diffInMonths($vivaDate);
                    return $monthsTaken <= 42; // <= 7 semesters
                })
                ->count();

            $onTimeRate = $total > 0 ? round(($onTimeGraduations / $total) * 100, 2) : 0;

            $clusterStats[] = [
                'cluster' => $cluster ?: 'Unassigned',
                'total_students' => $total,
                'on_time_graduations' => $onTimeGraduations,
                'on_time_rate' => $onTimeRate,
                'percentage_label' => round($onTimeRate) . '%',
            ];
        }

        // Sort by on-time rate descending
        usort($clusterStats, fn($a, $b) => $b['on_time_rate'] <=> $a['on_time_rate']);

        return $clusterStats;
    }

    /**
     * Get time-to-graduation trends for years 2017-2022
     * Grouped by cohort year from the historical CSV dataset.
     */
    public function getTimeToGraduationTrends(): array
    {
        $rows = collect($this->historicalDatasetRows())
            ->filter(fn(array $row) => ! empty($row['registration_date']))
            ->groupBy(fn(array $row) => Carbon::parse($row['registration_date'])->year);
        $trends = [];

        foreach ($rows as $year => $cohort) {
            // Only include years 2017-2022
            if ($year < 2017 || $year > 2022) {
                continue;
            }

            $graduatedStudents = $cohort->filter(fn(array $row) => ! empty($row['viva_date']));

            if ($graduatedStudents->count() === 0) {
                continue;
            }

            // Calculate average time to graduation in months
            $totalMonths = $graduatedStudents->sum(function (array $row) {
                $startDate = Carbon::parse($row['registration_date']);
                $vivaDate = Carbon::parse($row['viva_date']);

                return $startDate->diffInMonths($vivaDate);
            });

            $averageMonths = round($totalMonths / $graduatedStudents->count(), 1);
            $averageYears = round($averageMonths / 12, 2);

            $trends[] = [
                'year' => (int) $year,
                'cohort_label' => "Year $year",
                'avg_months' => $averageMonths,
                'avg_years' => $averageYears,
                'graduated_count' => $graduatedStudents->count(),
                'total_count' => $cohort->count(),
            ];
        }

        // Sort by year ascending
        usort($trends, fn($a, $b) => $a['year'] <=> $b['year']);

        return $trends;
    }

    /**
     * Get milestone completion rates
     * Measures % of all current students completing each checkpoint.
     */
    public function getMilestoneCompletionRates(): array
    {
        $students = User::role('student')->with('checkpoint')->get();

        $milestones = [
            'proposal_review' => [],
            'research_review' => [],
            'nos' => [],
            'final_draft' => [],
            'viva' => [],
        ];

        $total = $students->count();

        if ($total > 0) {
            $milestones['proposal_review'] = [
                'label' => 'Proposal Review',
                'completed' => $students->filter(fn(User $student) => $student->checkpoint?->proposal_review_date !== null)->count(),
                'total' => $total,
            ];

            $milestones['research_review'] = [
                'label' => 'Research Review',
                'completed' => $students->filter(fn(User $student) => $student->checkpoint?->research_review_date !== null)->count(),
                'total' => $total,
            ];

            $milestones['nos'] = [
                'label' => 'NOS',
                'completed' => $students->filter(fn(User $student) => $student->checkpoint?->nos_date !== null)->count(),
                'total' => $total,
            ];

            $milestones['final_draft'] = [
                'label' => 'Final Draft Submission',
                'completed' => $students->filter(fn(User $student) => $student->checkpoint?->final_draft_submission_date !== null)->count(),
                'total' => $total,
            ];

            $milestones['viva'] = [
                'label' => 'Viva',
                'completed' => $students->filter(fn(User $student) => $student->checkpoint?->viva_date !== null)->count(),
                'total' => $total,
            ];

            // Calculate percentages
            foreach ($milestones as &$milestone) {
                $milestone['completion_rate'] = round(
                    ($milestone['completed'] / $milestone['total']) * 100,
                    2
                );
                $milestone['percentage_label'] = round($milestone['completion_rate']) . '%';
            }
        }

        return $milestones;
    }

    /**
     * Get risk distribution across all students
     */
    public function getRiskDistribution(): array
    {
        $allStudents = User::role('student')->with('checkpoint')->get();

        $riskDistribution = [
            'low' => 0,
            'medium' => 0,
            'high' => 0,
            'unknown' => 0,
        ];

        foreach ($allStudents as $student) {
            $riskLevel = $student->checkpoint?->risk_level;
            
            if (!$riskLevel) {
                $riskDistribution['unknown']++;
            } else {
                $riskDistribution[strtolower($riskLevel)]++;
            }
        }

        $total = $allStudents->count();

        return [
            'low' => [
                'count' => $riskDistribution['low'],
                'percentage' => $total > 0 ? round(($riskDistribution['low'] / $total) * 100, 2) : 0,
            ],
            'medium' => [
                'count' => $riskDistribution['medium'],
                'percentage' => $total > 0 ? round(($riskDistribution['medium'] / $total) * 100, 2) : 0,
            ],
            'high' => [
                'count' => $riskDistribution['high'],
                'percentage' => $total > 0 ? round(($riskDistribution['high'] / $total) * 100, 2) : 0,
            ],
            'unknown' => [
                'count' => $riskDistribution['unknown'],
                'percentage' => $total > 0 ? round(($riskDistribution['unknown'] / $total) * 100, 2) : 0,
            ],
            'total' => $total,
        ];
    }

    /**
     * Get all analytics data in one call (with caching)
     */
    public function getAllAnalytics(): array
    {
        // Cache can be invalidated by running: php artisan cache:clear
        $datasetPath = config('ml.historical_dataset_path');
        $datasetVersion = is_string($datasetPath) && is_file($datasetPath) ? (string) filemtime($datasetPath) : 'missing';
        $cacheKey = 'dashboard_analytics_v2_' . date('Y-m-d') . '_' . $datasetVersion;
        
        return cache()->remember($cacheKey, 86400, function () {
            return [
                'cluster_statistics' => $this->getClusterStatistics(),
                'time_to_graduation_trends' => $this->getTimeToGraduationTrends(),
                'milestone_completion' => $this->getMilestoneCompletionRates(),
                'risk_distribution' => $this->getRiskDistribution(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<int, array{cluster:string,study_mode:string,registration_date:?string,proposal_review_date:?string,research_review_date:?string,nos_date:?string,final_draft_submission_date:?string,viva_date:?string}>
     */
    private function historicalDatasetRows(): array
    {
        if ($this->historicalDatasetCache !== null) {
            return $this->historicalDatasetCache;
        }

        $path = config('ml.historical_dataset_path');
        if (! is_string($path) || ! is_file($path)) {
            return $this->historicalDatasetCache = [];
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $headers = [];
        $rows = [];

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null] || $row === false) {
                continue;
            }

            if ($index === 0) {
                $headers = array_map(fn($header) => $this->normalizeDatasetHeader((string) $header), $row);
                continue;
            }

            $combined = [];
            foreach ($headers as $columnIndex => $header) {
                $combined[$header] = $this->normalizeDatasetValue($row[$columnIndex] ?? null);
            }

            $rows[] = [
                'cluster' => (string) ($combined['cluster'] ?? 'Unassigned'),
                'study_mode' => (string) ($combined['type'] ?? 'Full Time'),
                'registration_date' => $this->normalizeDatasetDate($combined['registration_date'] ?? null),
                'proposal_review_date' => $this->normalizeDatasetDate($combined['proposal_review_date'] ?? null),
                'research_review_date' => $this->normalizeDatasetDate($combined['research_review_date'] ?? null),
                'nos_date' => $this->normalizeDatasetDate($combined['nos_date'] ?? null),
                'final_draft_submission_date' => $this->normalizeDatasetDate($combined['final_draft_submission_date'] ?? null),
                'viva_date' => $this->normalizeDatasetDate($combined['viva_date'] ?? null),
            ];
        }

        return $this->historicalDatasetCache = $rows;
    }

    private function normalizeDatasetHeader(string $header): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($header)));
    }

    private function normalizeDatasetValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDatasetDate(mixed $value): ?string
    {
        $value = $this->normalizeDatasetValue($value);
        if ($value === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-m-Y', 'd M Y', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->startOfDay()->toDateString();
                }
            } catch (\Throwable) {
                // Try the next known format.
            }
        }

        try {
            return Carbon::parse($value)->startOfDay()->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
