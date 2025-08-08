<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddBookingSlotRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time'
        ];
    }
}
