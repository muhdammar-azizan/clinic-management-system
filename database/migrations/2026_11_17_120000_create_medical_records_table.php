<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            // A doctor with records cannot be deleted by accident.
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();

            // Step 1: visit details
            $table->date('visit_date');
            $table->string('chief_complaint');
            $table->text('symptoms')->nullable();

            // Step 2: clinical assessment
            $table->text('diagnosis');
            $table->text('treatment_plan')->nullable();
            $table->text('prescription')->nullable();
            $table->string('condition', 20)->default('stable');
            $table->date('follow_up_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'visit_date']);
        });

        // Step 3: attachments
        Schema::create('medical_record_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('other'); // prescription or other
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_attachments');
        Schema::dropIfExists('medical_records');
    }
};
