<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Services\RosterGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateRosterRequest extends FormRequest
{
    /** Session lengths the admin can choose, in minutes. */
    public const DURATIONS = [15, 20, 30, 45, 60];

    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Doctor->value)],
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $from = $this->input('from');

                    if (is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
                        && Carbon::parse($from)->diffInDays(Carbon::parse($value)) >= RosterGenerator::MAX_DAYS) {
                        $fail('Choose a range of '.RosterGenerator::MAX_DAYS.' days or fewer.');
                    }
                },
            ],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i', 'after:work_start'],
            'duration' => ['required', Rule::in(self::DURATIONS)],
            'break_start' => ['nullable', 'date_format:H:i', 'required_with:break_end', 'after_or_equal:work_start'],
            'break_end' => ['nullable', 'date_format:H:i', 'required_with:break_start', 'after:break_start', 'before_or_equal:work_end'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.required' => 'Choose at least one working day.',
            'work_end.after' => 'The end of the working day must be later than the start.',
            'break_end.after' => 'The break must end after it starts.',
            'break_start.after_or_equal' => 'The break must start within working hours.',
            'break_end.before_or_equal' => 'The break must end within working hours.',
            'from.after_or_equal' => 'The first date cannot be in the past.',
        ];
    }
}
