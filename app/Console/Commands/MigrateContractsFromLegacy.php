<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateContractsFromLegacy extends Command
{
    protected $signature = 'migrate:legacy-contracts
                                {--dry-run : Preview changes without inserting}
                                {--force  : Skip confirmation prompt}';

    protected $description = 'Migrate contracts, customers, garanties, item_types, contract_items, attachments and attachment_items from the legacy app database into this ERP system.';

    // -------------------------------------------------------------------------
    // Legacy-type  →  new enum value mapping
    // -------------------------------------------------------------------------
    private const GARANTIE_TYPE_MAP = [
        'garantie_soumission' => 'bid_bond',
        'garantie_bonne_execution' => 'performance',
        'garantie_avance' => 'advance_payment',
        'garantie_retenue' => 'retention',
    ];

    // -------------------------------------------------------------------------
    // Raw legacy data (extracted from backup_2026_017.sql)
    // -------------------------------------------------------------------------

    /** @var array<int, array<string, mixed>> */
    private array $legacyCustomers = [
        ['id' => 15, 'reference' => 'GTIM',      'company_name' => 'GROUPEMENT - TIMIMOUN',              'short_name' => 'GTIM',       'address' => null, 'phone' => null, 'email' => null, 'website' => null, 'registration_number' => null, 'notes' => null, 'created_at' => '2026-05-12 19:18:59', 'updated_at' => '2026-06-01 07:14:19'],
        ['id' => 16, 'reference' => 'SH-DP-ADR', 'company_name' => 'SONATRACH - DP - ADR',              'short_name' => 'SH-DP-ADR',  'address' => null, 'phone' => null, 'email' => null, 'website' => null, 'registration_number' => null, 'notes' => null, 'created_at' => '2026-05-12 20:01:05', 'updated_at' => '2026-06-01 07:14:37'],
        ['id' => 17, 'reference' => 'GTFT',      'company_name' => 'GROUPEMENT - TFT',                  'short_name' => 'GTFT',       'address' => null, 'phone' => null, 'email' => null, 'website' => null, 'registration_number' => null, 'notes' => null, 'created_at' => '2026-05-12 20:04:01', 'updated_at' => '2026-06-01 07:14:03'],
        ['id' => 18, 'reference' => 'SH/DP/STAH', 'company_name' => "SONATRACH - CPF d'ALRAR - STAH",   'short_name' => 'SH/DP/STAH', 'address' => null, 'phone' => null, 'email' => null, 'website' => null, 'registration_number' => null, 'notes' => null, 'created_at' => '2026-06-18 11:04:38', 'updated_at' => '2026-06-18 12:22:50'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $legacyGaranties = [
        ['id' => 11, 'reference' => 'G-GTFT-009',     'bank_name' => 'BEA', 'amount' => '1204711.20',  'started_at' => '2022-11-12', 'status' => 'active',    'type' => 'garantie_bonne_execution', 'created_at' => '2026-08-02 06:37:24', 'updated_at' => '2026-08-02 06:37:24'],
        ['id' => 12, 'reference' => 'G-SH/ADRAR-010', 'bank_name' => 'BEA', 'amount' => '4651600.00',  'started_at' => '2024-07-24', 'status' => 'active',    'type' => 'garantie_bonne_execution', 'created_at' => '2026-08-02 06:39:01', 'updated_at' => '2026-08-02 06:39:01'],
        ['id' => 13, 'reference' => 'G-STAH-011',     'bank_name' => 'BEA', 'amount' => '1592640.87',  'started_at' => '2026-02-26', 'status' => 'active',    'type' => 'garantie_bonne_execution', 'created_at' => '2026-08-02 06:40:14', 'updated_at' => '2026-08-02 06:40:14'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $legacyContracts = [
        [
            'id' => 2,
            'reference' => 'CO23-012-CHM',
            'object' => "Prestation des services de calibrage des instruments du skid fiscal metering d'exportation gaz",
            'date_signature' => '2023-08-20',
            'duree' => 36,
            'montant_global_prevu' => '24420000.00',
            'customer_id' => 15,
            'garantie_id' => null,
            'created_at' => '2026-04-26 14:28:15',
            'updated_at' => '2026-08-02 06:52:24',
        ],
        [
            'id' => 4,
            'reference' => '482-DAC-GTFT-2022 GMTM',
            'object' => 'Vérification du système de comptage fiscal',
            'date_signature' => '2022-11-12',
            'duree' => 55,
            'montant_global_prevu' => '60235560.00',
            'customer_id' => 17,
            'garantie_id' => 11,
            'created_at' => '2026-05-12 07:05:45',
            'updated_at' => '2026-08-02 06:41:01',
        ],
        [
            'id' => 5,
            'reference' => '14-SH-DP-ADR-TECH-2024',
            'object' => 'Prestation de la vérification périodique des systèmes de comptage transactionnel de la Direction Régionale ADRAR',
            'date_signature' => '2024-06-24',
            'duree' => 24,
            'montant_global_prevu' => '46516000.00',
            'customer_id' => 16,
            'garantie_id' => 12,
            'created_at' => '2026-05-12 10:39:17',
            'updated_at' => '2026-08-02 06:41:44',
        ],
        [
            'id' => 6,
            'reference' => 'I/ 57 /STAH.MNT/2025',
            'object' => "Prestations de vérification et étalonnage réglementaires des Skids de comptage fiscal de l'unité de traitement de gaz CPF d'Alrar - Stah",
            'date_signature' => '2026-02-15',
            'duree' => 36,
            'montant_global_prevu' => '82950045.50',
            'customer_id' => 18,
            'garantie_id' => 13,
            'created_at' => '2026-06-18 11:08:24',
            'updated_at' => '2026-08-02 06:40:47',
        ],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $legacyItemTypes = [
        ['id' => 1,  'designation' => 'PT',                 'created_at' => '2026-04-27 11:49:41', 'updated_at' => '2026-04-27 11:49:41'],
        ['id' => 2,  'designation' => 'TT',                 'created_at' => '2026-04-27 11:49:49', 'updated_at' => '2026-04-27 11:49:49'],
        ['id' => 3,  'designation' => 'ADC',                'created_at' => '2026-04-27 11:49:55', 'updated_at' => '2026-04-27 11:49:55'],
        ['id' => 4,  'designation' => 'SONDE',              'created_at' => '2026-04-27 11:50:00', 'updated_at' => '2026-04-27 11:50:00'],
        ['id' => 5,  'designation' => 'Calculateur',        'created_at' => '2026-05-11 11:47:15', 'updated_at' => '2026-05-11 11:47:15'],
        ['id' => 6,  'designation' => 'Chromatographs',     'created_at' => '2026-05-11 12:30:31', 'updated_at' => '2026-05-11 12:31:00'],
        ['id' => 7,  'designation' => 'Mob/D.Mob',          'created_at' => '2026-05-11 12:31:53', 'updated_at' => '2026-05-11 12:31:53'],
        ['id' => 8,  'designation' => 'Man Power',          'created_at' => '2026-05-11 12:32:51', 'updated_at' => '2026-05-11 12:32:51'],
        ['id' => 9,  'designation' => 'PT_ADC',             'created_at' => '2026-06-01 07:42:28', 'updated_at' => '2026-06-01 07:42:28'],
        ['id' => 10, 'designation' => 'PT_ADC_MP',          'created_at' => '2026-06-01 07:42:40', 'updated_at' => '2026-06-01 07:42:40'],
        ['id' => 11, 'designation' => 'TT_ADC_MP',          'created_at' => '2026-06-01 07:42:47', 'updated_at' => '2026-06-01 07:42:47'],
        ['id' => 12, 'designation' => 'TT_ADC',             'created_at' => '2026-06-01 07:43:00', 'updated_at' => '2026-06-01 07:43:00'],
        ['id' => 13, 'designation' => 'PT_MP',              'created_at' => '2026-06-01 07:43:09', 'updated_at' => '2026-06-01 07:43:09'],
        ['id' => 14, 'designation' => 'TT_MP',              'created_at' => '2026-06-01 07:43:21', 'updated_at' => '2026-06-01 07:43:21'],
        ['id' => 15, 'designation' => 'SONDE_MP',           'created_at' => '2026-06-01 07:43:43', 'updated_at' => '2026-06-01 07:43:43'],
        ['id' => 16, 'designation' => 'ADC_MP',             'created_at' => '2026-06-01 07:44:23', 'updated_at' => '2026-06-01 07:44:23'],
        ['id' => 17, 'designation' => 'Calculateur_MP',     'created_at' => '2026-06-01 07:44:42', 'updated_at' => '2026-06-01 07:44:42'],
        ['id' => 18, 'designation' => 'Chromatographs_MP',  'created_at' => '2026-06-01 07:45:22', 'updated_at' => '2026-06-01 07:45:22'],
        ['id' => 19, 'designation' => 'Chromatographs_GAZ', 'created_at' => '2026-06-01 07:46:47', 'updated_at' => '2026-06-01 07:46:47'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $legacyContractItems = [
        // contract_id = 2 (CO23-012-CHM)
        ['id' => 18,  'contract_id' => 2, 'item_type_id' => 1,    'designation' => 'Calibrage transmetteur de pression',                                                               'quantity' => 42, 'unit_price' => '45000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-04-28 19:50:36', 'updated_at' => '2026-05-12 19:43:45'],
        ['id' => 19,  'contract_id' => 2, 'item_type_id' => 2,    'designation' => 'Calibrage transmetteur de température',                                                              'quantity' => 42, 'unit_price' => '45000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-04-28 19:50:36', 'updated_at' => '2026-05-12 19:43:45'],
        ['id' => 20,  'contract_id' => 2, 'item_type_id' => 3,    'designation' => 'Tests de boucles et ADC des transmetteurs',                                                         'quantity' => 84, 'unit_price' => '50000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-04-28 19:50:36', 'updated_at' => '2026-05-12 19:43:45'],
        ['id' => 36,  'contract_id' => 2, 'item_type_id' => 4,    'designation' => 'Calibrage des sondes de température',                                                               'quantity' => 42, 'unit_price' => '50000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-04-29 23:13:49', 'updated_at' => '2026-05-12 19:43:45'],
        ['id' => 38,  'contract_id' => 2, 'item_type_id' => 8,    'designation' => 'Man Power',                                                                                         'quantity' => 6,  'unit_price' => '1550000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-03 05:46:55', 'updated_at' => '2026-05-12 19:43:45'],
        ['id' => 72,  'contract_id' => 2, 'item_type_id' => 5,    'designation' => 'S600+ Calculateur',                                                                                 'quantity' => 42, 'unit_price' => '120000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-05-12 19:43:45', 'updated_at' => '2026-05-12 19:43:45'],
        // contract_id = 5 (14-SH-DP-ADR-TECH-2024)
        ['id' => 114, 'contract_id' => 5, 'item_type_id' => 1,    'designation' => 'Transmetteur de pression relative',                                                                 'quantity' => 56, 'unit_price' => '142500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 115, 'contract_id' => 5, 'item_type_id' => 2,    'designation' => 'Transmetteur de Température',                                                                       'quantity' => 56, 'unit_price' => '142500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 116, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Sonde de Température',                                                                              'quantity' => 48, 'unit_price' => '95000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 117, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Calculateur',                                                                                       'quantity' => 48, 'unit_price' => '237500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 118, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Compteur de Gaz à Ultrasons',                                                                       'quantity' => 48, 'unit_price' => '142500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 119, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Chromatographe en Phase Gazeuse',                                                                   'quantity' => 24, 'unit_price' => '161500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-05-13 08:45:21', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 341, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Calculateur de débit/DECHRA',                                                                       'quantity' => 8,  'unit_price' => '120000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:41:44', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 342, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Débitmétre/DECHRA',                                                                                 'quantity' => 8,  'unit_price' => '75000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:41:44', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 343, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Indicateur transmetteur de pression différentielle/DECHRA',                                          'quantity' => 8,  'unit_price' => '80000.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:41:44', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 344, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Sonde de Température/DECHRA',                                                                       'quantity' => 8,  'unit_price' => '100000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:41:44', 'updated_at' => '2026-08-02 06:41:44'],
        ['id' => 345, 'contract_id' => 5, 'item_type_id' => null,  'designation' => 'Transmetteur Indicateur de Débit/DECHRA',                                                           'quantity' => 8,  'unit_price' => '110000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:41:44', 'updated_at' => '2026-08-02 06:41:44'],
        // contract_id = 4 (482-DAC-GTFT-2022 GMTM)
        ['id' => 309, 'contract_id' => 4, 'item_type_id' => 1,    'designation' => 'Calibration des 10 transmetteurs de pression',                                                      'quantity' => 6,  'unit_price' => '399500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 310, 'contract_id' => 4, 'item_type_id' => 1,    'designation' => 'Calibration des 8 transmetteurs de pression différentielle',                                        'quantity' => 6,  'unit_price' => '319600.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 311, 'contract_id' => 4, 'item_type_id' => 2,    'designation' => 'Calibration des 10 transmetteurs de température',                                                   'quantity' => 6,  'unit_price' => '399500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 312, 'contract_id' => 4, 'item_type_id' => 4,    'designation' => 'Calibration des 10 sondes de température',                                                          'quantity' => 6,  'unit_price' => '467500.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 313, 'contract_id' => 4, 'item_type_id' => 5,    'designation' => 'Vérification des acquisitions des 15 calculateurs CDN MECI',                                       'quantity' => 6,  'unit_price' => '2932500.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 314, 'contract_id' => 4, 'item_type_id' => 5,    'designation' => 'Maintenance + configuration si besoin des 15 calculateurs CDN MECI',                               'quantity' => 6,  'unit_price' => '800000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 315, 'contract_id' => 4, 'item_type_id' => 3,    'designation' => 'Tests des 40 boucles & ADC (Analog to Digital Converter)',                                         'quantity' => 6,  'unit_price' => '1938000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 316, 'contract_id' => 4, 'item_type_id' => 5,    'designation' => 'Vérification des 2 organes déprimogénes (orifices)',                                                'quantity' => 6,  'unit_price' => '850000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 317, 'contract_id' => 4, 'item_type_id' => 3,    'designation' => 'Vérification & maintenance des 6 débitmètres à turbine',                                           'quantity' => 6,  'unit_price' => '765000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        ['id' => 318, 'contract_id' => 4, 'item_type_id' => 7,    'designation' => 'Mob / démob',                                                                                       'quantity' => 6,  'unit_price' => '255000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-07-18 20:49:39', 'updated_at' => '2026-08-02 06:41:01'],
        // contract_id = 6 (I/ 57 /STAH.MNT/2025)
        ['id' => 237, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Vérification d'un débitmètre Coriolis du skid GPL (Proving)",                                      'quantity' => 12, 'unit_price' => '148800.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 239, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Vérification d'un débitmètre Coriolis du Condensats (Proving)",                                    'quantity' => 12, 'unit_price' => '148800.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 241, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Vérification (Étalonnage) de Compact Prover',                                                      'quantity' => 6,  'unit_price' => '720000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 243, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Vérification d'un chromatographe",                                                                 'quantity' => 6,  'unit_price' => '240000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 245, 'contract_id' => 6, 'item_type_id' => 12,   'designation' => 'Vérification des transmetteurs de température',                                                     'quantity' => 54, 'unit_price' => '97920.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 246, 'contract_id' => 6, 'item_type_id' => 9,    'designation' => 'Vérification des transmetteurs de pression absolue (ou relative)',                                 'quantity' => 54, 'unit_price' => '97920.00',    'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 247, 'contract_id' => 6, 'item_type_id' => 4,    'designation' => 'Vérification des sondes de température',                                                            'quantity' => 54, 'unit_price' => '106560.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'semestrielle', 'created_at' => '2026-06-18 12:23:18', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 319, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Expédition provisoire et calibration de la Jauge Etalon',                                          'quantity' => 1,  'unit_price' => '1152000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 320, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Expédition provisoire d'un débitmètre Coriolis du GPL pour maintenance en cas de défaillance",    'quantity' => 1,  'unit_price' => '816000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 321, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Expédition provisoire d'un débitmètre Coriolis du Condensats pour maintenance en cas de défaillance", 'quantity' => 1,  'unit_price' => '816000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 322, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Expédition provisoire pour réparation d'un débitmètre Ultrasonique de gaz naturel",               'quantity' => 4,  'unit_price' => '3633600.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 323, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Expédition provisoire d'un chromatographe pour réparation",                                        'quantity' => 2,  'unit_price' => '1152000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 324, 'contract_id' => 6, 'item_type_id' => 5,    'designation' => 'Vérification des calculateurs électroniques pour rampes GPL, Condensat et Gaz',                   'quantity' => 24, 'unit_price' => '172800.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 325, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Intervention sur un calculateur électronique en cas de défaillance',                               'quantity' => 2,  'unit_price' => '576000.00',   'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 326, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Trois (03) gaz étalons de PCS différents (50L), couvrant toute l'échelle de mesure",             'quantity' => 1,  'unit_price' => '5402879.99',  'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 327, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Easidew pro xp',                                                                                    'quantity' => 3,  'unit_price' => '2688000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 328, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Easidew Transmitter',                                                                               'quantity' => 3,  'unit_price' => '1152000.00',  'type' => 'service', 'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 329, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Sonde de Température Pt100/214CRWSMA1S4M05505LE1',                                                 'quantity' => 3,  'unit_price' => '131214.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 330, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Sonde de Température Pt100/214CRWSMA1S4M04030SLE1 / 0065N3...21XA',                                'quantity' => 3,  'unit_price' => '127559.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 331, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Sonde de Température Pt100/214CRWSMA1S4M04030SLE1 / 0065N3...21XA',                                'quantity' => 2,  'unit_price' => '127559.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 332, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Transmetteur de température/[0 à 120] °C',                                                          'quantity' => 2,  'unit_price' => '251098.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 333, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Transmetteur de température/[-10 à 100] °C',                                                        'quantity' => 2,  'unit_price' => '251098.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 334, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Transmetteur de pression/[0 à 120] barg',                                                          'quantity' => 1,  'unit_price' => '349418.00',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 335, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Transmetteur de pression/[0 à 100] barg',                                                          'quantity' => 1,  'unit_price' => '349418.00',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 336, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Carte P144 (Flow Computer – FloBoss S600+)',                                                       'quantity' => 2,  'unit_price' => '2178745.50',  'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 337, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'RELAIS ASSEMBLY- DOUBLE & DIRECT ACTING',                                                           'quantity' => 2,  'unit_price' => '483922.00',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 338, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Convertisseur I/P',                                                                                 'quantity' => 2,  'unit_price' => '463454.00',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 339, 'contract_id' => 6, 'item_type_id' => null,  'designation' => 'Positionneur DVC6200',                                                                              'quantity' => 2,  'unit_price' => '1156442.00',  'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
        ['id' => 340, 'contract_id' => 6, 'item_type_id' => null,  'designation' => "Kit joints de Compact Prover / 18''",                                                              'quantity' => 3,  'unit_price' => '444082.50',   'type' => 'supply',  'unit_cost' => '0.00', 'frequency' => 'annuelle',     'created_at' => '2026-08-02 06:40:47', 'updated_at' => '2026-08-02 06:40:47'],
    ];

    // =========================================================================

    /** @var array<int, array<string, mixed>> */
    private array $legacyAttachments = [
        ['id' => 14, 'mission_id' => 271, 'date' => '2023-09-22', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-001', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-04-30 21:20:19', 'updated_at' => '2026-05-13 10:03:01'],
        ['id' => 15, 'mission_id' => 273, 'date' => '2024-05-12', 'ods' => null, 'code_ref' => 'ATT-GMTM-2024-001', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-05-12 05:49:03', 'updated_at' => '2026-05-13 10:02:57'],
        ['id' => 16, 'mission_id' => 274, 'date' => '2024-10-27', 'ods' => null, 'code_ref' => 'ATT-GMTM-2024-002', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-05-12 06:12:23', 'updated_at' => '2026-05-13 10:02:14'],
        ['id' => 17, 'mission_id' => 275, 'date' => '2025-04-29', 'ods' => null, 'code_ref' => 'ATT-GMTM-2025-001', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-05-12 06:21:03', 'updated_at' => '2026-05-13 10:02:25'],
        ['id' => 18, 'mission_id' => 168, 'date' => '2025-10-28', 'ods' => null, 'code_ref' => 'ATT-GMTM-2025-002', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-05-12 06:29:07', 'updated_at' => '2026-05-13 10:02:36'],
        ['id' => 19, 'mission_id' => 259, 'date' => '2026-04-08', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-001', 'type' => 'service', 'frequency' => 'semestrielle', 'status' => 'approved', 'created_at' => '2026-05-12 06:33:03', 'updated_at' => '2026-05-13 10:02:52'],
        ['id' => 21, 'mission_id' => 279, 'date' => '2024-06-29', 'ods' => null, 'code_ref' => 'ATT-GMTM-2024-003', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 08:51:35', 'updated_at' => '2026-05-13 09:54:26'],
        ['id' => 22, 'mission_id' => 280, 'date' => '2024-06-15', 'ods' => null, 'code_ref' => 'ATT-GMTM-2024-004', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 09:00:07', 'updated_at' => '2026-05-13 09:55:04'],
        ['id' => 23, 'mission_id' => 281, 'date' => '2024-06-22', 'ods' => null, 'code_ref' => 'ATT-GMTM-2024-005', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 09:02:19', 'updated_at' => '2026-05-13 09:55:20'],
        ['id' => 24, 'mission_id' => 282, 'date' => '2025-08-09', 'ods' => null, 'code_ref' => 'ATT-GMTM-2025-003', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 09:08:07', 'updated_at' => '2026-05-13 09:55:43'],
        ['id' => 25, 'mission_id' => 283, 'date' => '2025-11-15', 'ods' => null, 'code_ref' => 'ATT-GMTM-2025-004', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 09:14:10', 'updated_at' => '2026-06-03 21:32:51'],
        ['id' => 26, 'mission_id' => 284, 'date' => '2025-11-21', 'ods' => null, 'code_ref' => 'ATT-GMTM-2025-005', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-05-13 09:16:36', 'updated_at' => '2026-05-13 09:56:12'],
        ['id' => 27, 'mission_id' => 277, 'date' => '2026-05-21', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-002', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-06-01 07:32:42', 'updated_at' => '2026-06-15 19:16:15'],
        ['id' => 28, 'mission_id' => 276, 'date' => '2026-06-10', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-003', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-06-03 11:26:45', 'updated_at' => '2026-06-15 19:16:34'],
        ['id' => 31, 'mission_id' => 285, 'date' => '2026-06-13', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-004', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:40:32'],
        ['id' => 32, 'mission_id' => 287, 'date' => '2026-07-04', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-005', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-18 20:49:57'],
        ['id' => 33, 'mission_id' => 288, 'date' => '2026-07-15', 'ods' => null, 'code_ref' => 'ATT-GMTM-2026-006', 'type' => 'service', 'frequency' => 'annuelle', 'status' => 'approved', 'created_at' => '2026-07-18 20:58:52', 'updated_at' => '2026-08-15 15:34:49'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $legacyAttachmentItems = [
        ['id' => 228, 'attachment_id' => 21, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 229, 'attachment_id' => 21, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 230, 'attachment_id' => 21, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 231, 'attachment_id' => 21, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 232, 'attachment_id' => 21, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 233, 'attachment_id' => 21, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 2, 'created_at' => '2026-05-13 09:54:17', 'updated_at' => '2026-05-13 09:54:17'],
        ['id' => 234, 'attachment_id' => 22, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 235, 'attachment_id' => 22, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 236, 'attachment_id' => 22, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 237, 'attachment_id' => 22, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 238, 'attachment_id' => 22, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 239, 'attachment_id' => 22, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:54:56', 'updated_at' => '2026-05-13 09:54:56'],
        ['id' => 240, 'attachment_id' => 23, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 241, 'attachment_id' => 23, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 242, 'attachment_id' => 23, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 243, 'attachment_id' => 23, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 244, 'attachment_id' => 23, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 245, 'attachment_id' => 23, 'contract_item_id' => 119, 'actual_quantity' => 1, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:16', 'updated_at' => '2026-05-13 09:55:16'],
        ['id' => 246, 'attachment_id' => 24, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 247, 'attachment_id' => 24, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 248, 'attachment_id' => 24, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 249, 'attachment_id' => 24, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 250, 'attachment_id' => 24, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 251, 'attachment_id' => 24, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:36', 'updated_at' => '2026-05-13 09:55:36'],
        ['id' => 252, 'attachment_id' => 25, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 253, 'attachment_id' => 25, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 254, 'attachment_id' => 25, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 255, 'attachment_id' => 25, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 256, 'attachment_id' => 25, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 257, 'attachment_id' => 25, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:55:47', 'updated_at' => '2026-05-13 09:55:47'],
        ['id' => 258, 'attachment_id' => 26, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 259, 'attachment_id' => 26, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 260, 'attachment_id' => 26, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 261, 'attachment_id' => 26, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 262, 'attachment_id' => 26, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 263, 'attachment_id' => 26, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 3, 'created_at' => '2026-05-13 09:56:07', 'updated_at' => '2026-05-13 09:56:07'],
        ['id' => 269, 'attachment_id' => 14, 'contract_item_id' => 18, 'actual_quantity' => 8, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:27', 'updated_at' => '2026-05-13 10:01:27'],
        ['id' => 270, 'attachment_id' => 14, 'contract_item_id' => 19, 'actual_quantity' => 8, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:27', 'updated_at' => '2026-05-13 10:01:27'],
        ['id' => 271, 'attachment_id' => 14, 'contract_item_id' => 20, 'actual_quantity' => 12, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:01:27', 'updated_at' => '2026-05-13 10:01:27'],
        ['id' => 272, 'attachment_id' => 14, 'contract_item_id' => 36, 'actual_quantity' => 2, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:27', 'updated_at' => '2026-05-13 10:01:27'],
        ['id' => 273, 'attachment_id' => 14, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:01:27', 'updated_at' => '2026-05-13 10:01:27'],
        ['id' => 274, 'attachment_id' => 15, 'contract_item_id' => 18, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:35', 'updated_at' => '2026-05-13 10:01:35'],
        ['id' => 275, 'attachment_id' => 15, 'contract_item_id' => 19, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:35', 'updated_at' => '2026-05-13 10:01:35'],
        ['id' => 276, 'attachment_id' => 15, 'contract_item_id' => 20, 'actual_quantity' => 14, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:01:35', 'updated_at' => '2026-05-13 10:01:35'],
        ['id' => 277, 'attachment_id' => 15, 'contract_item_id' => 36, 'actual_quantity' => 11, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:01:35', 'updated_at' => '2026-05-13 10:01:35'],
        ['id' => 278, 'attachment_id' => 15, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:01:35', 'updated_at' => '2026-05-13 10:01:35'],
        ['id' => 279, 'attachment_id' => 16, 'contract_item_id' => 18, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:05', 'updated_at' => '2026-05-13 10:02:05'],
        ['id' => 280, 'attachment_id' => 16, 'contract_item_id' => 19, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:05', 'updated_at' => '2026-05-13 10:02:05'],
        ['id' => 281, 'attachment_id' => 16, 'contract_item_id' => 20, 'actual_quantity' => 3, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:02:05', 'updated_at' => '2026-05-13 10:02:05'],
        ['id' => 282, 'attachment_id' => 16, 'contract_item_id' => 36, 'actual_quantity' => 11, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:05', 'updated_at' => '2026-05-13 10:02:05'],
        ['id' => 283, 'attachment_id' => 16, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:02:05', 'updated_at' => '2026-05-13 10:02:05'],
        ['id' => 284, 'attachment_id' => 17, 'contract_item_id' => 18, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:19', 'updated_at' => '2026-05-13 10:02:19'],
        ['id' => 285, 'attachment_id' => 17, 'contract_item_id' => 19, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:19', 'updated_at' => '2026-05-13 10:02:19'],
        ['id' => 286, 'attachment_id' => 17, 'contract_item_id' => 20, 'actual_quantity' => 14, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:02:19', 'updated_at' => '2026-05-13 10:02:19'],
        ['id' => 287, 'attachment_id' => 17, 'contract_item_id' => 36, 'actual_quantity' => 10, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:19', 'updated_at' => '2026-05-13 10:02:19'],
        ['id' => 288, 'attachment_id' => 17, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:02:19', 'updated_at' => '2026-05-13 10:02:19'],
        ['id' => 289, 'attachment_id' => 18, 'contract_item_id' => 18, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:30', 'updated_at' => '2026-05-13 10:02:30'],
        ['id' => 290, 'attachment_id' => 18, 'contract_item_id' => 19, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:30', 'updated_at' => '2026-05-13 10:02:30'],
        ['id' => 291, 'attachment_id' => 18, 'contract_item_id' => 20, 'actual_quantity' => 14, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:02:30', 'updated_at' => '2026-05-13 10:02:30'],
        ['id' => 292, 'attachment_id' => 18, 'contract_item_id' => 36, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:30', 'updated_at' => '2026-05-13 10:02:30'],
        ['id' => 293, 'attachment_id' => 18, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:02:30', 'updated_at' => '2026-05-13 10:02:30'],
        ['id' => 294, 'attachment_id' => 19, 'contract_item_id' => 18, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:46', 'updated_at' => '2026-05-13 10:02:46'],
        ['id' => 295, 'attachment_id' => 19, 'contract_item_id' => 19, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:46', 'updated_at' => '2026-05-13 10:02:46'],
        ['id' => 296, 'attachment_id' => 19, 'contract_item_id' => 20, 'actual_quantity' => 14, 'planned_quantity' => 14, 'created_at' => '2026-05-13 10:02:46', 'updated_at' => '2026-05-13 10:02:46'],
        ['id' => 297, 'attachment_id' => 19, 'contract_item_id' => 36, 'actual_quantity' => 9, 'planned_quantity' => 7, 'created_at' => '2026-05-13 10:02:46', 'updated_at' => '2026-05-13 10:02:46'],
        ['id' => 298, 'attachment_id' => 19, 'contract_item_id' => 38, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-05-13 10:02:46', 'updated_at' => '2026-05-13 10:02:46'],
        ['id' => 323, 'attachment_id' => 27, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 324, 'attachment_id' => 27, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 325, 'attachment_id' => 27, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 326, 'attachment_id' => 27, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 327, 'attachment_id' => 27, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 328, 'attachment_id' => 27, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 2, 'created_at' => '2026-06-03 12:05:56', 'updated_at' => '2026-06-03 12:05:56'],
        ['id' => 357, 'attachment_id' => 28, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 358, 'attachment_id' => 28, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 359, 'attachment_id' => 28, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 360, 'attachment_id' => 28, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 361, 'attachment_id' => 28, 'contract_item_id' => 118, 'actual_quantity' => 3, 'planned_quantity' => 4, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 362, 'attachment_id' => 28, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 2, 'created_at' => '2026-06-09 18:09:05', 'updated_at' => '2026-06-09 18:09:05'],
        ['id' => 369, 'attachment_id' => 31, 'contract_item_id' => 114, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 370, 'attachment_id' => 31, 'contract_item_id' => 115, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 371, 'attachment_id' => 31, 'contract_item_id' => 116, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 372, 'attachment_id' => 31, 'contract_item_id' => 117, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 373, 'attachment_id' => 31, 'contract_item_id' => 118, 'actual_quantity' => 4, 'planned_quantity' => 4, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 374, 'attachment_id' => 31, 'contract_item_id' => 119, 'actual_quantity' => 2, 'planned_quantity' => 2, 'created_at' => '2026-06-15 19:19:57', 'updated_at' => '2026-06-15 19:19:57'],
        ['id' => 375, 'attachment_id' => 32, 'contract_item_id' => 237, 'actual_quantity' => 2, 'planned_quantity' => 2, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 376, 'attachment_id' => 32, 'contract_item_id' => 239, 'actual_quantity' => 1, 'planned_quantity' => 2, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 377, 'attachment_id' => 32, 'contract_item_id' => 241, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 378, 'attachment_id' => 32, 'contract_item_id' => 243, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 379, 'attachment_id' => 32, 'contract_item_id' => 245, 'actual_quantity' => 9, 'planned_quantity' => 9, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 380, 'attachment_id' => 32, 'contract_item_id' => 246, 'actual_quantity' => 9, 'planned_quantity' => 9, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 381, 'attachment_id' => 32, 'contract_item_id' => 247, 'actual_quantity' => 9, 'planned_quantity' => 9, 'created_at' => '2026-07-04 11:55:46', 'updated_at' => '2026-07-04 11:55:46'],
        ['id' => 391, 'attachment_id' => 33, 'contract_item_id' => 309, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 392, 'attachment_id' => 33, 'contract_item_id' => 310, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 393, 'attachment_id' => 33, 'contract_item_id' => 311, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 394, 'attachment_id' => 33, 'contract_item_id' => 312, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 395, 'attachment_id' => 33, 'contract_item_id' => 314, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 396, 'attachment_id' => 33, 'contract_item_id' => 315, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 397, 'attachment_id' => 33, 'contract_item_id' => 316, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 398, 'attachment_id' => 33, 'contract_item_id' => 317, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
        ['id' => 399, 'attachment_id' => 33, 'contract_item_id' => 318, 'actual_quantity' => 1, 'planned_quantity' => 1, 'created_at' => '2026-07-18 21:00:21', 'updated_at' => '2026-07-18 21:00:21'],
    ];

    // =========================================================================

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║      Legacy Contracts Migration Tool                 ║');
        $this->info('╚══════════════════════════════════════════════════════╝');

        if ($dryRun) {
            $this->warn('  ⚡ DRY-RUN mode — nothing will be written to the DB.');
        }

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm('This will INSERT legacy data into the ERP database. Continue?')) {
                $this->line('Aborted.');

                return self::FAILURE;
            }
        }

        $this->migrateCustomers($dryRun);
        $this->migrateGaranties($dryRun);
        $this->migrateContracts($dryRun);
        $this->migrateItemTypes($dryRun);
        $this->migrateContractItems($dryRun);
        $this->migrateAttachments($dryRun);
        $this->migrateAttachmentItems($dryRun);

        $this->newLine();
        $this->info('✅  Migration completed successfully.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function migrateCustomers(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 1/3  Customers ────────────────────────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyCustomers as $row) {
            $exists = DB::table('customers')->where('reference', $row['reference'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Customer already exists: {$row['reference']}");
                $skipped++;

                continue;
            }

            $this->line("   [INSERT] {$row['reference']} — {$row['company_name']}");

            if (! $dryRun) {
                DB::table('customers')->insert([
                    'id' => $row['id'],
                    'reference' => $row['reference'],
                    'company_name' => $row['company_name'],
                    'short_name' => $row['short_name'],
                    'address' => $row['address'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'website' => $row['website'],
                    'registration_number' => $row['registration_number'],
                    'notes' => $row['notes'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateGaranties(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 2/3  Garanties (Bank Guarantees) ──────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyGaranties as $row) {
            $exists = DB::table('garanties')->where('reference', $row['reference'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Garantie already exists: {$row['reference']}");
                $skipped++;

                continue;
            }

            $newType = self::GARANTIE_TYPE_MAP[$row['type']] ?? 'other';
            $newStatus = in_array($row['status'], ['active', 'expired', 'released', 'cancelled'], true)
                ? $row['status']
                : 'other';

            $this->line("   [INSERT] {$row['reference']} — {$row['bank_name']} — {$row['amount']} DA  (type: {$newType})");

            if (! $dryRun) {
                DB::table('garanties')->insert([
                    'id' => $row['id'],
                    'reference' => $row['reference'],
                    'bank_name' => $row['bank_name'],
                    'amount' => $row['amount'],
                    'started_at' => $row['started_at'],
                    'status' => $newStatus,
                    'type' => $newType,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateContracts(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 3/3  Contracts ────────────────────────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyContracts as $row) {
            $exists = DB::table('contracts')->where('reference', $row['reference'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Contract already exists: {$row['reference']}");
                $skipped++;

                continue;
            }

            // Resolve customer_id in the NEW DB (find by ID we inserted above)
            $customerExists = DB::table('customers')->where('id', $row['customer_id'])->exists();
            if (! $customerExists) {
                $this->warn("   [WARN] Customer ID {$row['customer_id']} not found — contract {$row['reference']} will have null customer.");
            }

            // Resolve garantie_id
            $warrantyExists = $row['garantie_id'] !== null
                ? DB::table('garanties')->where('id', $row['garantie_id'])->exists()
                : false;

            if ($row['garantie_id'] !== null && ! $warrantyExists) {
                $this->warn("   [WARN] Garantie ID {$row['garantie_id']} not found — garantie_id will be null.");
            }

            $amount = number_format((float) $row['montant_global_prevu'], 2, '.', '');
            $this->line("   [INSERT] {$row['reference']} — {$amount} DA — customer_id={$row['customer_id']} garantie_id={$row['garantie_id']}");

            if (! $dryRun) {
                DB::table('contracts')->insert([
                    'id' => $row['id'],
                    'reference' => $row['reference'],
                    'object' => $row['object'],
                    'date_signature' => $row['date_signature'],
                    'duree' => $row['duree'],
                    'montant_global_prevu' => $row['montant_global_prevu'],
                    'customer_id' => $customerExists ? $row['customer_id'] : null,
                    'garantie_id' => $warrantyExists ? $row['garantie_id'] : null,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateItemTypes(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 4/5  Item Types ───────────────────────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyItemTypes as $row) {
            $exists = DB::table('item_types')->where('id', $row['id'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Item type already exists: {$row['designation']}");
                $skipped++;

                continue;
            }

            $this->line("   [INSERT] #{$row['id']} {$row['designation']}");

            if (! $dryRun) {
                DB::table('item_types')->insert([
                    'id' => $row['id'],
                    'designation' => $row['designation'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateContractItems(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 5/5  Contract Items ───────────────────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyContractItems as $row) {
            $exists = DB::table('contract_items')->where('id', $row['id'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Item #{$row['id']} already exists: {$row['designation']}");
                $skipped++;

                continue;
            }

            $total = number_format((float) $row['unit_price'] * $row['quantity'], 2, '.', ',');
            $this->line("   [INSERT] #{$row['id']} [{$row['contract_id']}] {$row['designation']} — {$row['quantity']} × {$row['unit_price']} DA = {$total} DA");

            if (! $dryRun) {
                DB::table('contract_items')->insert([
                    'id' => $row['id'],
                    'contract_id' => $row['contract_id'],
                    'item_type_id' => $row['item_type_id'],
                    'designation' => $row['designation'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'type' => $row['type'],
                    'unit_cost' => $row['unit_cost'],
                    'frequency' => $row['frequency'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateAttachments(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 6/7  Attachments ─────────────────────────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyAttachments as $row) {
            $exists = DB::table('attachments')->where('id', $row['id'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Attachment #{$row['id']} already exists: {$row['code_ref']}");
                $skipped++;

                continue;
            }

            $this->line("   [INSERT] Attachment #{$row['id']} — {$row['code_ref']} (Mission #{$row['mission_id']})");

            if (! $dryRun) {
                DB::table('attachments')->insert([
                    'id' => $row['id'],
                    'mission_id' => $row['mission_id'],
                    'date' => $row['date'],
                    'ods' => $row['ods'],
                    'code_ref' => $row['code_ref'],
                    'type' => $row['type'],
                    'frequency' => $row['frequency'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }

    // -------------------------------------------------------------------------

    private function migrateAttachmentItems(bool $dryRun): void
    {
        $this->newLine();
        $this->info('── 7/7  Attachment Items (Consumptions) ──────────────');

        $inserted = 0;
        $skipped = 0;

        foreach ($this->legacyAttachmentItems as $row) {
            $exists = DB::table('attachment_items')->where('id', $row['id'])->exists();

            if ($exists) {
                $this->line("   [SKIP] Consumption #{$row['id']} already exists");
                $skipped++;

                continue;
            }

            $this->line("   [INSERT] Consumption #{$row['id']}: Item #{$row['contract_item_id']} in Attachment #{$row['attachment_id']} (Actual: {$row['actual_quantity']} / Planned: {$row['planned_quantity']})");

            if (! $dryRun) {
                DB::table('attachment_items')->insert([
                    'id' => $row['id'],
                    'attachment_id' => $row['attachment_id'],
                    'contract_item_id' => $row['contract_item_id'],
                    'actual_quantity' => $row['actual_quantity'],
                    'planned_quantity' => $row['planned_quantity'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $inserted++;
        }

        $this->info("   → {$inserted} inserted, {$skipped} skipped.");
    }
}
