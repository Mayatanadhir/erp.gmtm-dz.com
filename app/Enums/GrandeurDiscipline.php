<?php

declare(strict_types=1);

namespace App\Enums;

enum GrandeurDiscipline: string
{
    case Temperature = 'temperature';
    case Pressure = 'pressure';
    case Current = 'current';
    case Voltage = 'voltage';
    case Resistance = 'resistance';
    case Frequency = 'frequency';
    case Pulse = 'pulse';
    case Dimensional = 'dimensional';
    case Mass = 'mass';
    case Flow = 'flow';
    case Generic = 'generic';

    /**
     * Auto-detect the physical discipline from name and unit symbol.
     */
    public static function detect(?string $name, ?string $symbol = null): self
    {
        $nameLower = mb_strtolower(trim((string) $name));
        $symbolLower = mb_strtolower(trim((string) $symbol));
        $symbolExact = trim((string) $symbol);

        // Temperature (°C, °F, K)
        if (
            str_contains($nameLower, 'temp') ||
            str_contains($nameLower, 'chaleur') ||
            str_contains($nameLower, 'حرارة') ||
            in_array($symbolExact, ['°C', '°F', 'K', 'degC', 'degF', 'C'], true)
        ) {
            return self::Temperature;
        }

        // Pressure (Bar, mBar, psi, Pa, kPa, MPa)
        if (
            str_contains($nameLower, 'press') ||
            str_contains($nameLower, 'bar') ||
            str_contains($nameLower, 'ضغط') ||
            in_array($symbolLower, ['bar', 'mbar', 'psi', 'pa', 'kpa', 'mpa'], true)
        ) {
            return self::Pressure;
        }

        // Current (A, mA, µA, uA)
        if (
            str_contains($nameLower, 'courant') ||
            str_contains($nameLower, 'current') ||
            str_contains($nameLower, 'amp') ||
            str_contains($nameLower, 'تيار') ||
            in_array($symbolLower, ['ma', 'a', 'µa', 'ua'], true)
        ) {
            return self::Current;
        }

        // Voltage (V, mV, kV)
        if (
            str_contains($nameLower, 'tension') ||
            str_contains($nameLower, 'volt') ||
            str_contains($nameLower, 'voltage') ||
            str_contains($nameLower, 'جهد') ||
            in_array($symbolLower, ['v', 'mv', 'kv'], true)
        ) {
            return self::Voltage;
        }

        // Resistance (Ω, kΩ, MΩ, Ohm)
        if (
            str_contains($nameLower, 'resist') ||
            str_contains($nameLower, 'ohm') ||
            str_contains($nameLower, 'مقاوم') ||
            str_contains($symbolExact, 'Ω') ||
            in_array($symbolLower, ['ohm', 'kohm', 'mohm'], true)
        ) {
            return self::Resistance;
        }

        // Frequency (Hz, kHz, MHz)
        if (
            str_contains($nameLower, 'frequen') ||
            str_contains($nameLower, 'تردد') ||
            in_array($symbolLower, ['hz', 'khz', 'mhz'], true)
        ) {
            return self::Frequency;
        }

        // Pulse (imp, pulse)
        if (
            str_contains($nameLower, 'impuls') ||
            str_contains($nameLower, 'impult') ||
            str_contains($nameLower, 'pulse') ||
            str_contains($nameLower, 'نبض') ||
            in_array($symbolLower, ['imp', 'pls', 'pulse'], true)
        ) {
            return self::Pulse;
        }

        // Dimensional (mm, cm, m, in)
        if (
            str_contains($nameLower, 'dimens') ||
            str_contains($nameLower, 'long') ||
            str_contains($nameLower, 'dist') ||
            str_contains($nameLower, 'طول') ||
            str_contains($nameLower, 'أبعاد') ||
            in_array($symbolLower, ['mm', 'cm', 'm', 'in', 'inch'], true)
        ) {
            return self::Dimensional;
        }

        // Mass (g, kg, mg, t)
        if (
            str_contains($nameLower, 'mass') ||
            str_contains($nameLower, 'poids') ||
            str_contains($nameLower, 'وزن') ||
            str_contains($nameLower, 'كتلة') ||
            in_array($symbolLower, ['g', 'kg', 'mg', 't'], true)
        ) {
            return self::Mass;
        }

        // Flow (L/min, m3/h)
        if (
            str_contains($nameLower, 'debit') ||
            str_contains($nameLower, 'flow') ||
            str_contains($nameLower, 'تدفق') ||
            in_array($symbolLower, ['l/min', 'm3/h', 'l/h', 'gpm'], true)
        ) {
            return self::Flow;
        }

        return self::Generic;
    }

    /**
     * Get the translated display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Temperature => __('Temperature'),
            self::Pressure => __('Pressure'),
            self::Current => __('Electrical Current'),
            self::Voltage => __('Voltage'),
            self::Resistance => __('Electrical Resistance'),
            self::Frequency => __('Frequency'),
            self::Pulse => __('Pulse / Counter'),
            self::Dimensional => __('Dimensional'),
            self::Mass => __('Mass & Weight'),
            self::Flow => __('Flow Rate'),
            self::Generic => __('Physical Standard'),
        };
    }

    /**
     * Get the FontAwesome icon class name.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Temperature => 'fa-temperature-high',
            self::Pressure => 'fa-tachometer-alt',
            self::Current => 'fa-bolt',
            self::Voltage => 'fa-bolt-lightning',
            self::Resistance => 'fa-microchip',
            self::Frequency => 'fa-wave-square',
            self::Pulse => 'fa-satellite-dish',
            self::Dimensional => 'fa-ruler-combined',
            self::Mass => 'fa-scale-balanced',
            self::Flow => 'fa-wind',
            self::Generic => 'fa-sliders-h',
        };
    }

    /**
     * Get the semantic color palette token key.
     */
    public function colorKey(): string
    {
        return match ($this) {
            self::Temperature => 'rose',
            self::Pressure => 'sky',
            self::Current => 'amber',
            self::Voltage => 'indigo',
            self::Resistance => 'emerald',
            self::Frequency => 'fuchsia',
            self::Pulse => 'purple',
            self::Dimensional => 'cyan',
            self::Mass => 'slate',
            self::Flow => 'teal',
            self::Generic => 'gray',
        };
    }

    /**
     * Get the text color Tailwind classes.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::Temperature => 'text-rose-600 dark:text-rose-400',
            self::Pressure => 'text-sky-600 dark:text-sky-400',
            self::Current => 'text-amber-600 dark:text-amber-400',
            self::Voltage => 'text-indigo-600 dark:text-indigo-400',
            self::Resistance => 'text-emerald-600 dark:text-emerald-400',
            self::Frequency => 'text-fuchsia-600 dark:text-fuchsia-400',
            self::Pulse => 'text-purple-600 dark:text-purple-400',
            self::Dimensional => 'text-cyan-600 dark:text-cyan-400',
            self::Mass => 'text-slate-600 dark:text-slate-400',
            self::Flow => 'text-teal-600 dark:text-teal-400',
            self::Generic => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get the border, background, and text classes for badges and pills.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Temperature => 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/20 dark:bg-rose-500/20 dark:border-rose-500/30',
            self::Pressure => 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/20 dark:bg-sky-500/20 dark:border-sky-500/30',
            self::Current => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20 dark:bg-amber-500/20 dark:border-amber-500/30',
            self::Voltage => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/20 dark:bg-indigo-500/20 dark:border-indigo-500/30',
            self::Resistance => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20 dark:bg-emerald-500/20 dark:border-emerald-500/30',
            self::Frequency => 'bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-300 border-fuchsia-500/20 dark:bg-fuchsia-500/20 dark:border-fuchsia-500/30',
            self::Pulse => 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/20 dark:bg-purple-500/20 dark:border-purple-500/30',
            self::Dimensional => 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border-cyan-500/20 dark:bg-cyan-500/20 dark:border-cyan-500/30',
            self::Mass => 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-500/20 dark:bg-slate-500/20 dark:border-slate-500/30',
            self::Flow => 'bg-teal-500/10 text-teal-700 dark:text-teal-300 border-teal-500/20 dark:bg-teal-500/20 dark:border-teal-500/30',
            self::Generic => 'bg-gray-500/10 text-gray-700 dark:text-gray-300 border-gray-500/20 dark:bg-gray-500/20 dark:border-gray-600/30',
        };
    }

    /**
     * Get the container class for round/square icon backgrounds.
     */
    public function iconContainerClass(): string
    {
        return match ($this) {
            self::Temperature => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 dark:bg-rose-500/20 border border-rose-500/20 dark:border-rose-500/30',
            self::Pressure => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 dark:bg-sky-500/20 border border-sky-500/20 dark:border-sky-500/30',
            self::Current => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 dark:bg-amber-500/20 border border-amber-500/20 dark:border-amber-500/30',
            self::Voltage => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 dark:bg-indigo-500/20 border border-indigo-500/20 dark:border-indigo-500/30',
            self::Resistance => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 dark:bg-emerald-500/20 border border-emerald-500/20 dark:border-emerald-500/30',
            self::Frequency => 'bg-fuchsia-500/10 text-fuchsia-600 dark:text-fuchsia-400 dark:bg-fuchsia-500/20 border border-fuchsia-500/20 dark:border-fuchsia-500/30',
            self::Pulse => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 dark:bg-purple-500/20 border border-purple-500/20 dark:border-purple-500/30',
            self::Dimensional => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 dark:bg-cyan-500/20 border border-cyan-500/20 dark:border-cyan-500/30',
            self::Mass => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 dark:bg-slate-500/20 border border-slate-500/20 dark:border-slate-600/30',
            self::Flow => 'bg-teal-500/10 text-teal-600 dark:text-teal-400 dark:bg-teal-500/20 border border-teal-500/20 dark:border-teal-500/30',
            self::Generic => 'bg-gray-500/10 text-gray-600 dark:text-gray-400 dark:bg-gray-500/20 border border-gray-500/20 dark:border-gray-600/30',
        };
    }

    /**
     * Get Chart.js hexadecimal line/marker color.
     */
    public function chartHexColor(): string
    {
        return match ($this) {
            self::Temperature => '#e11d48',
            self::Pressure => '#0284c7',
            self::Current => '#d97706',
            self::Voltage => '#6366f1',
            self::Resistance => '#059669',
            self::Frequency => '#c026d3',
            self::Pulse => '#9333ea',
            self::Dimensional => '#0891b2',
            self::Mass => '#475569',
            self::Flow => '#0d9488',
            self::Generic => '#4b5563',
        };
    }

    /**
     * Get semantic badge variant compatible with <x-badge>.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Temperature => 'danger',
            self::Pressure => 'info',
            self::Current => 'warning',
            self::Voltage => 'primary',
            self::Resistance => 'success',
            default => 'neutral',
        };
    }

    /**
     * Get all cases values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
