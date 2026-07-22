<?php

namespace App\Service\Formatter;

use App\Entity\MeasureProtection;

class MeasureProtectionFormatter
{
    public function format(MeasureProtection $measureProtection) : array
    {
        return [
            'id' => $measureProtection->getId(),
            'measure_type' => $measureProtection->getMeasureType(),
            'judgment_date' => $measureProtection->getJudgmentDate()?->format('Y-m-d'),
            'start_date' => $measureProtection->getStartDate()?->format('Y-m-d'),
            'end_date' => $measureProtection->getEndDate()?->format('Y-m-d'),
            'duration_years' => $measureProtection->getDurationYears(),
            'tribunal_name' => $measureProtection->getTribunalName(),
            'tribunal_city' => $measureProtection->getTribunalCity(),
            'cabinet_number' => $measureProtection->getCabinetNumber(),
            'note' => $measureProtection->getNote(),
            'created_at' => $measureProtection->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $measureProtection->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param MeasureProtection[] $measureProtections
     */
    public function formatCollection(array $measureProtections): array
    {
        $formattedMeasureProtections = [];

        foreach ($measureProtections as $measureProtection) {
            $formattedMeasureProtections[] = $this->format($measureProtection);
        }

        return $formattedMeasureProtections;
    }
}
