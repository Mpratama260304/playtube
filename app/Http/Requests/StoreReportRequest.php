<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', 'in:video,comment,user'],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', 'in:spam,inappropriate,violence,harassment,copyright,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
