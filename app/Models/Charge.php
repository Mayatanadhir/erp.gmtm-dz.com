<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Backward-compatibility alias for the Expense model.
 */
class Charge extends Expense
{
    // Inherits all attributes, relationships, casts, and scopes from Expense
}
