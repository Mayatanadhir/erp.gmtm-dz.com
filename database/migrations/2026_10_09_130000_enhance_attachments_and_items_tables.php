<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Resolve historical duplicate code_refs before adding unique index
        if (Schema::hasTable('attachments')) {
            $duplicates = DB::table('attachments')
                ->select('code_ref')
                ->whereNotNull('code_ref')
                ->groupBy('code_ref')
                ->havingRaw('count(*) > 1')
                ->pluck('code_ref');

            foreach ($duplicates as $dupRef) {
                $rows = DB::table('attachments')->where('code_ref', $dupRef)->orderBy('id')->get();
                foreach ($rows as $index => $row) {
                    if ($index > 0) {
                        $year = ! empty($row->date) ? date('Y', strtotime((string) $row->date)) : '2026';
                        $newRef = 'ATT-GMTM-'.$year.'-'.str_pad((string) ($row->id), 3, '0', STR_PAD_LEFT);
                        DB::table('attachments')->where('id', $row->id)->update(['code_ref' => $newRef]);
                    }
                }
            }
        }

        // 2. Add performance indexes and unique constraints
        Schema::table('attachments', function (Blueprint $table): void {
            if (! Schema::hasIndex('attachments', 'attachments_code_ref_unique')) {
                $table->unique('code_ref', 'attachments_code_ref_unique');
            }
            if (! Schema::hasIndex('attachments', 'attachments_status_date_index')) {
                $table->index(['status', 'date'], 'attachments_status_date_index');
            }
        });

        Schema::table('attachment_items', function (Blueprint $table): void {
            if (! Schema::hasIndex('attachment_items', 'attachment_items_att_item_index')) {
                $table->index(['attachment_id', 'contract_item_id'], 'attachment_items_att_item_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment_items', function (Blueprint $table): void {
            $table->dropIndex('attachment_items_att_item_index');
        });

        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropUnique('attachments_code_ref_unique');
            $table->dropIndex('attachments_status_date_index');
        });
    }
};
