<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('review_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_file_id')->constrained('student_files')->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->enum('auto_review_status', ['pending', 'success', 'failed'])->default('pending');
            $table->enum('manual_review_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('auto_review_content')->nullable();
            $table->text('manual_review_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('review_results');
    }
}; 