<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,docx',
                function ($attribute, $value, $fail) {
                    $mime = $value->getMimeType();
                    $allowed = [
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ];
                    if (! in_array($mime, $allowed, true)) {
                        $fail('Only PDF and DOCX files are allowed.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'File size must not exceed 10 MB.',
            'file.mimes' => 'Only PDF and DOCX files are allowed.',
        ];
    }
}
