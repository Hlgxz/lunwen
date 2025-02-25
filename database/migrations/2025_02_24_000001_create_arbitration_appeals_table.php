<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('arbitration_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_result_id')->constrained('review_results')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('arbitrator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('appeal_content')->comment('申诉内容');
            $table->text('arbitration_opinion')->nullable()->comment('仲裁意见');
            $table->enum('status', ['pending', 'processing', 'completed'])->default('pending')->comment('仲裁状态');
            $table->enum('result', ['pending', 'approved', 'rejected'])->default('pending')->comment('仲裁结果');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('arbitration_appeals');
    }
}; 