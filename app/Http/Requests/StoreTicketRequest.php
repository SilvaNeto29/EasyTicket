<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'  => ['required', 'integer', 'exists:projects,id'],
            'title'       => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', new Enum(TicketStatus::class)],
            'priority'    => ['required', new Enum(TicketPriority::class)],
            'due_date'    => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }
}
