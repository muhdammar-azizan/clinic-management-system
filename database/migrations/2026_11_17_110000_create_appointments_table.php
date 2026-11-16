<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('roster_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('booked');
            $table->text('reason')->nullable();

            // Mirrors roster_slot_id while the appointment is booked, and is null otherwise.
            // The unique index on it stops two active bookings for one slot,
            // while cancelled or completed appointments never block the slot.
            $table->foreignId('active_slot_id')->nullable()->unique()->constrained('roster_slots')->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
