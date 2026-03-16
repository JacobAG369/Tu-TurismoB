<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->create('reviews', function (Blueprint $collection) {
            $collection->index('usuario_id');
            $collection->index('lugar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('reviews');
    }
};
