<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('course_outcome_attainments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('term');
            $table->unsignedBigInteger('co_id');
            $table->integer('score')->default(0);
            $table->integer('max')->default(0);
            $table->float('semester_total')->default(0);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('co_id')->references('id')->on('course_outcomes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_outcome_attainments');
    }
};
