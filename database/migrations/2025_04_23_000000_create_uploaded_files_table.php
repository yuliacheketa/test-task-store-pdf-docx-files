<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->softDeletes();
            $table->timestamps();

            $table->index('uploaded_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
