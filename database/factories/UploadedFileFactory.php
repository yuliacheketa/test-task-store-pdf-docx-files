<?php

namespace Database\Factories;

use App\Models\UploadedFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UploadedFileFactory extends Factory
{
    protected $model = UploadedFile::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();
        $ext = $this->faker->randomElement(['pdf', 'docx']);
        $stored = "{$uuid}.{$ext}";

        return [
            'original_name' => $this->faker->word().'.'.$ext,
            'stored_name' => $stored,
            'path' => 'uploads/'.$stored,
            'mime_type' => $ext === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => $this->faker->numberBetween(10_000, 5_000_000),
            'uploaded_at' => now(),
        ];
    }
}

