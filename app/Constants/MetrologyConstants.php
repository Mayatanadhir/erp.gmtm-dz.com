<?php

declare(strict_types=1);

namespace App\Constants;

final class MetrologyConstants
{
    /**
     * Reference Conditions
     */
    public const string REF_TEMPERATURE_C = '20.00';

    public const string STD_ATMOSPHERIC_PRESSURE_BAR = '1.01325';

    /**
     * Thermal Expansion Coefficients (1/°C)
     */
    public const string G_C_MILD_STEEL = '0.00003300';          // Carbon steel pipe prover

    public const string G_CM_STAINLESS_STEEL = '0.00005100';    // Stainless steel 304/316 test measure

    public const string G_A_SVP_CYLINDER = '0.00003400';        // Compact SVP cylinder area expansion

    public const string G_L_SVP_SHAFT = '0.00000120';           // Invar sensor shaft linear expansion

    /**
     * Mechanical Elasticity Modulus (bar)
     * (30 x 10^6 psi ~= 2068427 bar)
     */
    public const string E_MODULUS_STEEL_BAR = '2068427.00';

    /**
     * Water Compressibility (F)
     */
    public const string WATER_COMPRESSIBILITY_BAR = '0.0000464';

    public const string WATER_COMPRESSIBILITY_KPA = '0.000000464';

    /**
     * Regulatory Repeatability Limit (OAM / API MPMS Ch. 4)
     */
    public const string MAX_REPEATABILITY_PERCENT = '0.020';

    /**
     * Water Density Constants (ISO 8222 / Tanaka 2001)
     * rho(t) = A5 * [1 - ((t + A1)^2 * (t + A2)) / (A3 * (t + A4))]
     */
    public const string WATER_DENS_A1 = '-3.983035';

    public const string WATER_DENS_A2 = '301.797';

    public const string WATER_DENS_A3 = '522528.9';

    public const string WATER_DENS_A4 = '69.34881';

    public const string WATER_DENS_A5 = '999.974950';

    /**
     * Precision Scales for Calculations
     */
    public const int INTERNAL_CALCULATION_SCALE = 12;

    public const int DEFAULT_FACTOR_SCALE = 8;

    public const int DEFAULT_VOLUME_SCALE = 5;

    public const int DEFAULT_PERCENT_SCALE = 4;
}
