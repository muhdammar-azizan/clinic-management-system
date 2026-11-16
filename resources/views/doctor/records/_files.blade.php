{{-- Step 3. $record is null on create. --}}
<x-card title="Attachments">
    <div class="grid gap-4">
        @if ($record && $record->attachments->isNotEmpty())
            <div>
                <p class="text-sm font-medium text-ink">Current files</p>
                <ul class="mt-2 divide-y divide-line rounded-card border border-line">
                    @foreach ($record->attachments as $attachment)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                            <span class="min-w-0">
                                <a href="{{ route('doctor.records.attachments.show', $attachment) }}" class="font-medium">{{ $attachment->original_name }}</a>
                                <span class="ms-2 text-xs text-muted">{{ $attachment->isPrescription() ? 'Prescription' : 'Attachment' }} &middot; {{ number_format($attachment->size / 1024, 0) }} KB</span>
                            </span>
                            <label class="inline-flex items-center gap-2 text-xs text-muted">
                                <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" class="rounded border-line text-danger focus:ring-danger"
                                       @checked(in_array($attachment->id, (array) old('remove_attachments', [])))>
                                Remove
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-field label="{{ $record ? 'Add prescription files' : 'Prescription files' }}" name="prescription_files"
                 hint="The patient can download these. PDF, JPG or PNG, up to 5 files of 5 MB each.">
            <input id="prescription_files" name="prescription_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"
                   class="block w-full text-sm text-ink file:me-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-2 file:text-sm file:font-medium file:text-deep hover:file:bg-primary/20">
            <x-input-error :messages="$errors->get('prescription_files.*')" />
        </x-field>

        <x-field label="{{ $record ? 'Add other attachments' : 'Other attachments' }}" name="attachments"
                 hint="Lab results, scans or reports. PDF, JPG or PNG, up to 5 files of 5 MB each.">
            <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"
                   class="block w-full text-sm text-ink file:me-3 file:rounded-md file:border-0 file:bg-primary-soft file:px-3 file:py-2 file:text-sm file:font-medium file:text-deep hover:file:bg-primary/20">
            <x-input-error :messages="$errors->get('attachments.*')" />
        </x-field>

        @unless ($record)
            <p class="text-sm text-muted">Attachments are optional. Saving the record also marks this appointment as completed.</p>
        @endunless
    </div>
</x-card>
