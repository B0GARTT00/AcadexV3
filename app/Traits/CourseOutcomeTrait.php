<?php

namespace App\Traits;

use App\Models\Activity;
use Illimunate\Database\Eloquent\Collection;

trait CourseOutcomeTrait
{
	/**
	 * Compute Course Outcome (CO) attainment for a student across terms.
	 *
	 * Formula:
	 *   CO% per term = (Sum of raw scores for that CO in the term ÷ Sum of max possible scores for that CO in the term) × 100
	 *   Semester Total CO% = (Sum of raw scores for that CO across all terms ÷ Sum of max possible scores for that CO across all terms) × 100
	 *
	 * Example CO mapping per term:
	 *   Prelim:   Activity1→CO2, Activity2→CO5, Activity3→CO4, OCR1→CO4, OCR2→CO3, OCR3→CO6, Exam→CO5
	 *   Midterm:  Activity1→CO3, Activity2→CO4, Activity3→CO2, OCR1→CO6, OCR2→CO1, OCR3→CO5, Exam→CO1
	 *   Prefinal: Activity1→CO1, Activity2→CO6, Activity3→CO4, OCR1→CO3, OCR2→CO2, OCR3→CO5, Exam→CO6
	 *   Final:    Activity1→CO5, Activity2→CO6, Activity3→CO2, OCR1→CO1, OCR2→CO4, OCR3→CO3, Exam→CO2
	 *
	 * Usage:
	 *   - Each student is a row. Each term has activities mapped to COs.
	 *   - Enter Score and Max for each activity.
	 *   - If a CO is not assessed, use 0 for both Score and Max.
	 *   - After all terms, a Total row sums all terms for each CO.
	 */
	public function computeCoAttainment(array $studentScores, array $activityCoMap): array
	{
		// $studentScores: [term => [activity => ['score' => int, 'max' => int]]]
		// $activityCoMap: [term => [activity => co_id]]
		$coResults = [];
		$coTotals = [];

		// Collect per-term CO scores and maxes
		foreach ($studentScores as $term => $activities) {
			foreach ($activities as $activity => $scoreData) {
				$coId = $activityCoMap[$term][$activity] ?? null;
				if (!$coId) continue;
				if (!isset($coResults[$term][$coId])) {
					$coResults[$term][$coId] = ['score' => 0, 'max' => 0];
				}
				$coResults[$term][$coId]['score'] += $scoreData['score'];
				$coResults[$term][$coId]['max'] += $scoreData['max'];
			}
		}

		// Calculate CO% per term (matches your sheet)
		$coPercentPerTerm = [];
		foreach ($coResults as $term => $cos) {
			foreach ($cos as $coId => $data) {
				$percent = ($data['max'] > 0) ? ($data['score'] / $data['max']) * 100 : 0;
				$coPercentPerTerm[$term][$coId] = round($percent, 0); // round to nearest integer
				// Accumulate for semester total
				if (!isset($coTotals[$coId])) {
					$coTotals[$coId] = ['score' => 0, 'max' => 0];
				}
				$coTotals[$coId]['score'] += $data['score'];
				$coTotals[$coId]['max'] += $data['max'];
			}
		}

		// Calculate Semester Total CO% (matches your sheet)
		$semesterTotal = [];
		$semesterRaw = [];
		$semesterMax = [];
		foreach ($coTotals as $coId => $data) {
			$percent = ($data['max'] > 0) ? ($data['score'] / $data['max']) * 100 : 0;
			$semesterTotal[$coId] = round($percent, 0); // round to nearest integer
			$semesterRaw[$coId] = $data['score'];
			$semesterMax[$coId] = $data['max'];
		}

		return [
			'per_term' => $coPercentPerTerm,
			'semester_total' => $semesterTotal,
			'semester_raw' => $semesterRaw,
			'semester_max' => $semesterMax,
		];
	}
}