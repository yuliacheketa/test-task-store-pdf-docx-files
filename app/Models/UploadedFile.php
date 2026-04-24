<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UploadedFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'size' => 'integer',
    ];

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('uploaded_at', '<=', now()->subHours(24));
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
