<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudentCheckpoint;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DashboardAnalyticsService
{
    /**
     * Get graduation success rates grouped by cluster
     * Analyzes past 3 years of data
     */
    public function getClusterStatistics(): array
    {
        $threeYearsAgo = Carbon::now()->subYears(3);
        
        $students = User::role('student')
            ->where('start_date', '<=', $threeYearsAgo)
            ->with('checkpoint')
            ->get();

        $clusterGroups = $students->groupBy('cluster');
        $clusterStats = [];

        foreach ($clusterGroups as $cluster => $clusterStudents) {
            $total = $clusterStudents->count();
            
            // Graduated = has viva_date completed (not null)
            $graduated = $clusterStudents
                ->filter(fn(User $s) => $s->checkpoint?->viva_date !== null)
                ->count();

            $successRate = $total > 0 ? round(($graduated / $total) * 100, 2) : 0;

            $clusterStats[] = [
                'cluster' => $cluster ?: 'Unassigned',
                'total_students' => $total,
                'graduated_students' => $graduated,
                'success_rate' => $successRate,
                'percentage_label' => round($successRate) . '%',
            ];
        }

        // Sort by success rate descending
        usort($clusterStats, fn($a, $b) => $b['success_rate'] <=> $a['success_rate']);

        return $clusterStats;
    }

    /**
     * Get time-to-graduation trends over past 3 years
     * Grouped by cohort year
     */
    public function getTimeToGraduationTrends(): array
    {
        $threeYearsAgo = Carbon::now()->subYears(3);
        
        $students = User::role('student')
            ->where('start_date', '<=', $threeYearsAgo)
            ->with('checkpoint')
            ->get();

        $cohortGroups = $students->groupBy(fn(User $s) => $s->start_date->year);
        $trends = [];

        foreach ($cohortGroups as $year => $cohort) {
            $graduatedStudents = $cohort->filter(
                fn(User $s) => $s->checkpoint?->viva_date !== null &&
                               $s->start_date !== null
            );

            if ($graduatedStudents->count() === 0) {
                continue;
            }

            // Calculate average time to graduation in months
            $totalMonths = $graduatedStudents->sum(function (User $student) {
                $startDate = $student->start_date;
                $vivaDate = $student->checkpoint->viva_date;
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
     * Measures % of students completing each checkpoint on-time
     */
    public function getMilestoneCompletionRates(): array
    {
        $threeYearsAgo = Carbon::now()->subYears(3);
        
        $checkpoints = StudentCheckpoint::whereHas('user', fn($q) => 
            $q->where('start_date', '<=', $threeYearsAgo)
        )
        ->with('user')
        ->get();

        $milestones = [
            'proposal_review' => [],
            'research_review' => [],
            'nos' => [],
            'final_draft' => [],
            'viva' => [],
        ];

        $total = $checkpoints->count();

        if ($total > 0) {
            $milestones['proposal_review'] = [
                'label' => 'Proposal Review',
                'completed' => $checkpoints->whereNotNull('proposal_review_date')->count(),
                'total' => $total,
            ];

            $milestones['research_review'] = [
                'label' => 'Research Review',
                'completed' => $checkpoints->whereNotNull('research_review_date')->count(),
                'total' => $total,
            ];

            $milestones['nos'] = [
                'label' => 'NOS',
                'completed' => $checkpoints->whereNotNull('nos_date')->count(),
                'total' => $total,
            ];

            $milestones['final_draft'] = [
                'label' => 'Final Draft Submission',
                'completed' => $checkpoints->whereNotNull('final_draft_submission_date')->count(),
                'total' => $total,
            ];

            $milestones['viva'] = [
                'label' => 'Viva',
                'completed' => $checkpoints->whereNotNull('viva_date')->count(),
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
        $cacheKey = 'dashboard_analytics_' . date('Y-m-d');
        
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
}
