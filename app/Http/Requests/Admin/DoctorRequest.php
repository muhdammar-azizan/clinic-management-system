<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $doctor = $this->route('doctor');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($doctor?->id),
            ],
            // Required when creating an account, optional when editing.
            'password' => [$doctor ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'service_id' => ['required', Rule::exists('services', 'id')],
            'license_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
