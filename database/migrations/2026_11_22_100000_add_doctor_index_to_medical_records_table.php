<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The doctor's record list filters by doctor_id and sorts by visit_date.
        Schema::table('medical_records', function (Blueprint $table) {
            $table->index(['doctor_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'visit_date']);
        });
    }
};
