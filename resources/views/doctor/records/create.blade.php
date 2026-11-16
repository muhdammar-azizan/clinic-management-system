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
                <x-card title="Visit details">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Visit date" name="visit_date" required>
                            <x-text-input id="visit_date" name="visit_date" type="date" class="block w-full"
                                          :value="old('visit_date', $appointment->slot->slot_date->toDateString())"
                                          max="{{ today()->toDateString() }}" required />
                        </x-field>

                        <x-field label="Service">
                            <x-text-input class="block w-full" :value="$appointment->service->name" disabled />
                        </x-field>

                        <x-field label="Chief complaint" name="chief_complaint" required class="sm:col-span-2"
                                 hint="{{ $appointment->reason ? 'Patient said: '.$appointment->reason : 'The main reason for the visit.' }}">
                            <x-text-input id="chief_complaint" name="chief_complaint" class="block w-full" maxlength="255"
                                          :value="old('chief_complaint', $appointment->reason)" required />
                        </x-field>

                        <x-field label="Symptoms" name="symptoms" class="sm:col-span-2">
                            <x-textarea id="symptoms" name="symptoms" rows="3" class="block w-full" maxlength="2000">{{ old('symptoms') }}</x-textarea>
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- Step 2 --}}
            <div x-ref="step2" x-show="step === 2" x-cloak>
                <x-card title="Clinical assessment">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Diagnosis" name="diagnosis" required class="sm:col-span-2">
                            <x-textarea id="diagnosis" name="diagnosis" rows="3" class="block w-full" maxlength="2000" required>{{ old('diagnosis') }}</x-textarea>
                        </x-field>

                        <x-field label="Treatment plan" name="treatment_plan" class="sm:col-span-2">
                            <x-textarea id="treatment_plan" name="treatment_plan" rows="3" class="block w-full" maxlength="2000">{{ old('treatment_plan') }}</x-textarea>
                        </x-field>

                        <x-field label="Prescription" name="prescription" class="sm:col-span-2" hint="Medicines and dosage. You can also attach a prescription file in the next step.">
                            <x-textarea id="prescription" name="prescription" rows="3" class="block w-full" maxlength="2000">{{ old('prescription') }}</x-textarea>
                        </x-field>

                        <x-field label="Patient condition" name="condition" required>
                            <x-select id="condition" name="condition" class="block w-full" required>
                                @foreach (\App\Enums\ConditionStatus::cases() as $condition)
                                    <option value="{{ $condition->value }}" @selected(old('condition', 'stable') === $condition->value)>{{ $condition->label() }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Follow-up date" name="follow_up_date">
                            <x-text-input id="follow_up_date" name="follow_up_date" type="date" class="block w-full" :value="old('follow_up_date')" />
                        </x-field>
                    </div>
                </x-card>
            </div>

            {{-- Step 3 --}}
            <div x-ref="step3" x-show="step === 3" x-cloak>
                <x-card title="Attachments">
                    <div class="grid gap-4">
                        <x-field label="Prescription files" name="prescription_files"
                                 hint="The patient can download these. PDF, JPG or PNG, up to 5 files of 5 MB each.">
                            <input id="prescription_files" name="prescription_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"
                                   class="block w-full text-sm text-ink file:me-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-2 file:text-sm file:font-medium file:text-deep hover:file:bg-primary/20">
                            <x-input-error :messages="$errors->get('prescription_files.*')" />
                        </x-field>

                        <x-field label="Other attachments" name="attachments"
                                 hint="Lab results, scans or reports. PDF, JPG or PNG, up to 5 files of 5 MB each.">
                            <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"
                                   class="block w-full text-sm text-ink file:me-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-2 file:text-sm file:font-medium file:text-deep hover:file:bg-primary/20">
                            <x-input-error :messages="$errors->get('attachments.*')" />
                        </x-field>

                        <p class="text-sm text-muted">Attachments are optional. Saving the record also marks this appointment as completed.</p>
                    </div>
                </x-card>
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
