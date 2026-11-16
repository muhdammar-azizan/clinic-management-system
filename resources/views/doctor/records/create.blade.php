<x-app-layout>
    @php
        // Re-open the step that has a validation error.
        $stepFields = [
            1 => ['visit_date', 'chief_complaint', 'symptoms'],
            2 => ['diagnosis', 'treatment_plan', 'prescription', 'condition', 'follow_up_date'],
        ];
        $initialStep = 1;
        if ($errors->any()) {
            $initialStep = 3;
            foreach ($stepFields as $number => $fields) {
                if ($errors->hasAny($fields)) {
                    $initialStep = $number;
                    break;
                }
            }
        }
        $steps = [1 => 'Visit details', 2 => 'Clinical assessment', 3 => 'Attachments'];
    @endphp

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-page-header title="New Medical Record" :subtitle="$appointment->patient->name.' · '.$appointment->appointment_code">
            <x-slot name="actions">
                <x-button :href="route('doctor.appointments.edit', $appointment)" variant="ghost">Cancel</x-button>
            </x-slot>
        </x-page-header>

        <form method="POST" action="{{ route('doctor.records.store') }}" enctype="multipart/form-data"
              x-data="{
                  step: {{ $initialStep }},
                  next() {
                      for (const field of this.$refs['step' + this.step].querySelectorAll('input, select, textarea')) {
                          if (! field.reportValidity()) return;
                      }
                      this.step++;
                  },
                  back() { this.step--; },
              }"
              class="space-y-6">
            @csrf
            <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">

            {{-- Progress --}}
            <ol class="flex items-center gap-2 text-sm" aria-label="Progress">
                @foreach ($steps as $number => $label)
                    <li class="flex items-center gap-2" :class="step >= {{ $number }} ? 'text-deep' : 'text-muted'">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full border text-xs font-semibold"
                              :class="step >= {{ $number }} ? 'border-primary bg-primary text-white' : 'border-line bg-white'">{{ $number }}</span>
                        <span class="hidden font-medium sm:inline">{{ $label }}</span>
                        @unless ($loop->last)
                            <span class="mx-1 hidden h-px w-8 bg-line sm:block"></span>
                        @endunless
                    </li>
                @endforeach
            </ol>

            {{-- Step 1 --}}
            <div x-ref="step1" x-show="step === 1">
                @include('doctor.records._visit')
            </div>

            {{-- Step 2 --}}
            <div x-ref="step2" x-show="step === 2" x-cloak>
                @include('doctor.records._assessment')
            </div>

            {{-- Step 3 --}}
            <div x-ref="step3" x-show="step === 3" x-cloak>
                @include('doctor.records._files')
            </div>

            {{-- Navigation --}}
            <div class="flex justify-between">
                <x-button variant="secondary" x-show="step > 1" x-cloak @click="back()">Back</x-button>
                <span x-show="step === 1"></span>

                <x-button x-show="step < 3" @click="next()">Next</x-button>
                <x-button type="submit" x-show="step === 3" x-cloak>Save record</x-button>
            </div>
        </form>
    </div>
</x-app-layout>
