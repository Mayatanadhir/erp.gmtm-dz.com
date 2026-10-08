<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class MissionConflictException extends RuntimeException
{
    /**
     * @param  array<int, array{name: string, mission: string}>  $employeeConflicts
     * @param  array<int, array{name: string, mission: string}>  $equipmentConflicts
     */
    public function __construct(
        public readonly array $employeeConflicts = [],
        public readonly array $equipmentConflicts = []
    ) {
        $messages = [];

        if (! empty($employeeConflicts)) {
            $empLines = array_map(
                fn (array $c): string => sprintf('%s (%s)', $c['name'], $c['mission']),
                $employeeConflicts
            );
            $messages[] = __('Employees already engaged in active missions during this timeframe: ').implode(', ', $empLines);
        }

        if (! empty($equipmentConflicts)) {
            $eqLines = array_map(
                fn (array $c): string => sprintf('%s (%s)', $c['name'], $c['mission']),
                $equipmentConflicts
            );
            $messages[] = __('Equipment/Vehicles deployed in other active missions: ').implode(', ', $eqLines);
        }

        parent::__construct(implode(' | ', $messages));
    }
}
