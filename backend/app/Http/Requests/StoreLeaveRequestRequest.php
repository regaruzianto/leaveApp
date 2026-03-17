<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only non-admin users can submit leave requests
        $user = $this->user();
        return $user && !$user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason'        => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required'    => 'Jenis cuti wajib dipilih.',
            'leave_type_id.exists'      => 'Jenis cuti tidak valid.',
            'start_date.required'       => 'Tanggal mulai wajib diisi.',
            'start_date.date'           => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'end_date.required'         => 'Tanggal selesai wajib diisi.',
            'end_date.date'             => 'Format tanggal selesai tidak valid.',
            'end_date.after_or_equal'   => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'reason.required'           => 'Alasan cuti wajib diisi.',
            'reason.max'                => 'Alasan cuti maksimal 1000 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validasi gagal. Periksa kembali data yang diinput.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
