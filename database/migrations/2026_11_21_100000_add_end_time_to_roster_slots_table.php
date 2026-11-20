<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_slots', function (Blueprint $table) {
            // Nullable only so that slots created before this column existed keep working.
            $table->time('end_time')->nullable()->after('start_time');
        });

        // Give existing slots a 30 minute session so every row has a time range.
        DB::table('roster_slots')->orderBy('id')->each(function ($slot) {
            $start = Carbon::createFromFormat('H:i', substr((string) $slot->start_time, 0, 5));
            $end = $start->copy()->addMinutes(30);

            DB::table('roster_slots')->where('id', $slot->id)->update([
                // Never wrap past midnight.
                'end_time' => $end->isSameDay($start) ? $end->format('H:i') : '23:59',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('roster_slots', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
};
