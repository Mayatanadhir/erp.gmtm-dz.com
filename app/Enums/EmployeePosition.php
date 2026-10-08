<?php

declare(strict_types=1);

namespace App\Enums;

enum EmployeePosition: string
{
    case GeneralManager = 'general_manager';
    case SeniorMeteringEngineer = 'senior_metering_engineer';
    case MeteringEngineer = 'metering_engineer';
    case SeniorInstrumentationEngineer = 'senior_instrumentation_engineer';
    case MeteringTechnician = 'metering_technician';
    case InstrumentationTechnician = 'instrumentation_technician';

    /**
     * Get the translated label for the position.
     */
    public function label(?string $locale = null): string
    {
        return match ($this) {
            self::GeneralManager => __('General Manager', [], $locale),
            self::SeniorMeteringEngineer => __('Senior Metering Engineer', [], $locale),
            self::MeteringEngineer => __('Metering Engineer', [], $locale),
            self::SeniorInstrumentationEngineer => __('Senior Instrumentation Engineer', [], $locale),
            self::MeteringTechnician => __('Metering Technician', [], $locale),
            self::InstrumentationTechnician => __('Instrumentation Technician', [], $locale),
        };
    }

    /**
     * Get the semantic badge variant for the position.
     *
     * Unified Design System Classification:
     * - Management: 'primary' (GMTM corporate brand identity)
     * - Engineering: 'info' (Specialist & technical engineering)
     * - Technicians: 'neutral' (Field operations & instrumentation support)
     */
    public function badgeVariant(): string
    {
        if ($this->isManagement()) {
            return 'primary';
        }

        if ($this->isEngineer()) {
            return 'info';
        }

        if ($this->isTechnician()) {
            return 'neutral';
        }

        return 'neutral';
    }

    /**
     * Get the semantic color token for the position.
     */
    public function color(): string
    {
        return match ($this->badgeVariant()) {
            'primary' => 'brand',
            'info' => 'indigo',
            default => 'gray',
        };
    }

    /**
     * Get the badge CSS classes for the position (legacy compatibility).
     */
    public function badgeClass(): string
    {
        return match ($this->badgeVariant()) {
            'primary' => 'bg-brand-600/10 text-brand-800 dark:text-brand-300 border border-brand-500/20',
            'info' => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-500/20',
            default => 'bg-gray-500/10 text-gray-700 dark:text-gray-300 border border-gray-500/20 dark:border-gray-600/30',
        };
    }

    /**
     * Check if the position is an engineering role.
     */
    public function isEngineer(): bool
    {
        return in_array($this, [
            self::SeniorMeteringEngineer,
            self::MeteringEngineer,
            self::SeniorInstrumentationEngineer,
        ], true);
    }

    /**
     * Check if the position is a technician role.
     */
    public function isTechnician(): bool
    {
        return in_array($this, [
            self::MeteringTechnician,
            self::InstrumentationTechnician,
        ], true);
    }

    /**
     * Check if the position is management.
     */
    public function isManagement(): bool
    {
        return $this === self::GeneralManager;
    }

    /**
     * Get all enum values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
