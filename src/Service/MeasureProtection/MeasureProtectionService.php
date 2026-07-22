<?php

namespace App\Service\MeasureProtection;

use App\Entity\Dossier;
use App\Entity\MeasureProtection;
use App\Entity\User;
use App\Enum\MeasureProtectionType;
use App\Repository\MeasureProtectionRepository;
use Doctrine\ORM\EntityManagerInterface;

class MeasureProtectionService
{
    public function __construct(
        private readonly MeasureProtectionRepository $measureProtectionRepository,
        private readonly EntityManagerInterface $em
    )
    {}

    /**
     * @return MeasureProtection[]
     */
    public function getByDossierId(int $dossierId, User $user) : array
    {
        return $this->measureProtectionRepository->findByDossierIdAndUser($dossierId, $user);
    }

    public function getCurrentByDossierId(int $dossierId, User $user) : MeasureProtection
    {
        $measureProtection = $this->measureProtectionRepository->findCurrentByDossierIdAndUser($dossierId, $user);

        if (!$measureProtection) {
            throw new \RuntimeException(
                'Aucune mesure de protection en cours n’a été trouvée.'
            );
        }

        return $measureProtection;
    }

    public function create(Dossier $dossier, array $data) : MeasureProtection
    {
        $this->validateRequiredData($data);

        $measureType = $this->validateMeasureType($data['measure_type']);

        $measureProtection = new MeasureProtection();

        $measureProtection
            ->setDossier($dossier)
            ->setMeasureType($measureType)
            ->setJudgmentDate($this->createDate($data['judgment_date'], 'date du jugement'))
            ->setStartDate($this->createDate($data['start_date'], 'date de début'));

        $this->applyOptionalData($measureProtection, $data);

        $this->validateDates($measureProtection);

        $this->em->persist($measureProtection);

        return $measureProtection;
    }

    public function update(MeasureProtection $measureProtection, array $data) : MeasureProtection
    {
        if (array_key_exists('measure_type', $data)) {
            $measureProtection->setMeasureType(
                $this->validateMeasureType($data['measure_type'])
            );
        }

        if (array_key_exists('judgment_date', $data)) {
            $measureProtection->setJudgmentDate(
                $this->createDate(
                    $data['judgment_date'],
                    'date du jugement'
                )
            );
        }

        if (array_key_exists('start_date', $data)) {
            $measureProtection->setStartDate(
                $this->createDate(
                    $data['start_date'],
                    'date de début'
                )
            );
        }

        $this->applyOptionalData($measureProtection, $data);

        $this->validateDates($measureProtection);

        $measureProtection->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $this->em->flush();

        return $measureProtection;
    }

    private function applyOptionalData(MeasureProtection $measureProtection, array $data) : void
    {
        if (array_key_exists('end_date', $data)) {
            $endDate = $data['end_date'];

            if ($endDate === null || $endDate === '') {
                $measureProtection->setEndDate(null);
            } else {
                $measureProtection->setEndDate(
                    $this->createDate(
                        $endDate,
                        'date de fin'
                    )
                );
            }
        }

        if (array_key_exists('duration_years', $data)) {
            $measureProtection->setDurationYears(
                $this->nullablePositiveInteger($data['duration_years'], 'durée de la mesure')
            );
        }

        if (array_key_exists('tribunal_name', $data)) {
            $measureProtection->setTribunalName(
                $this->nullableString($data['tribunal_name'])
            );
        }

        if (array_key_exists('tribunal_city', $data)) {
            $measureProtection->setTribunalCity(
                $this->nullableString(
                    $data['tribunal_city']
                )
            );
        }

        if (array_key_exists('cabinet_number', $data)) {
            $measureProtection->setCabinetNumber(
                $this->nullableString(
                    $data['cabinet_number']
                )
            );
        }

        if (array_key_exists('note', $data)) {
            $measureProtection->setNote(
                $this->nullableString(
                    $data['note']
                )
            );
        }
    }

    private function validateRequiredData(array $data): void
    {
        $requiredFields = ['measure_type', 'judgment_date', 'start_date'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Le champ "%s" est obligatoire.',
                        $field
                    )
                );
            }
        }
    }

    private function validateDates(MeasureProtection $measureProtection): void 
    {
        $judgmentDate = $measureProtection->getJudgmentDate();
        $startDate = $measureProtection->getStartDate();
        $endDate = $measureProtection->getEndDate();

        if ($judgmentDate !== null && $startDate !== null && $startDate < $judgmentDate) {
            throw new \InvalidArgumentException(
                'La date de début ne peut pas être antérieure à la date du jugement.'
            );
        }

        if ($endDate !== null && $startDate !== null && $endDate < $startDate
        ) {
            throw new \InvalidArgumentException(
                'La date de fin ne peut pas être antérieure à la date de début.'
            );
        }
    }

    private function createDate(mixed $value, string $fieldLabel): \DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s est invalide.',
                    $fieldLabel
                )
            );
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        $errors = \DateTimeImmutable::getLastErrors();

        $dateHasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (!$date || $dateHasErrors ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s doit respecter le format YYYY-MM-DD.',
                    $fieldLabel
                )
            );
        }

        return $date;
    }

    private function nullablePositiveInteger(mixed $value, string $fieldLabel) : ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s doit être un nombre entier.',
                    $fieldLabel
                )
            );
        }

        $value = (int) $value;

        if ($value <= 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s doit être supérieure à zéro.',
                    $fieldLabel
                )
            );
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function requiredString(mixed $value, string $fieldLabel): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le champ "%s" est obligatoire.',
                    $fieldLabel
                )
            );
        }

        return $value;
    }

    private function validateMeasureType(mixed $value) : string
    {
        $measureType = $this->requiredString($value, 'type de mesure');

        if (!in_array($measureType, MeasureProtectionType::TYPES, true)) {
            throw new \InvalidArgumentException(
                'Le type de mesure renseigné n\'est pas valide.'
            );
        }

        return $measureType;
    }

}
