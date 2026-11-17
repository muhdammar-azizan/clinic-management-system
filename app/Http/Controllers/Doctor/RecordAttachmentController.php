<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordAttachmentController extends Controller
{
    /**
     * Download an attachment of a record the doctor wrote. Files live on the private disk.
     */
    public function __invoke(Request $request, MedicalRecordAttachment $attachment): StreamedResponse
    {
        $this->authorize('download', $attachment);
        abort_unless(Storage::exists($attachment->path), 404);

        return Storage::download($attachment->path, $attachment->original_name);
    }
}
