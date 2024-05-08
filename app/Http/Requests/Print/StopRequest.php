<?php

namespace App\Http\Requests\Print;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StopRequest extends FormRequest
{

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'data' => [],
            'success' => false,
            'message' => 'The given data is invalid',
            'errors' => $validator->errors()
        ], 422);
        throw new ValidationException($validator, $response);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required',
            'process_id' => 'required',
            'process_type' => 'required'
        ];
    }
}