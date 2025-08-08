<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'slots' => 'required|array|min:1',
            'slots.*.start_time' => [
                'required',
                'date',
                'after_or_equal:'.now()->format('Y-m-d H:i:s'),
            ],
            'slots.*.end_time' => [
                'required',
                'date',
                'after:slots.*.start_time',
            ],
        ];
    }
    public function messages()
    {
        return [
            'slots.*.start_time.after_or_equal' => 'Дата начала должна быть не раньше текущего времени',
            'slots.*.end_time.after' => 'Дата окончания должна быть позже даты начала',
        ];
    }
}
