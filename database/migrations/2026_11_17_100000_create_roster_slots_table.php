<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->string('status', 20)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            // A doctor cannot have two slots starting at the same time.
            $table->unique(['doctor_id', 'slot_date', 'start_time']);
            $table->index(['slot_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_slots');
    }
};
