<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('issuing_authority_hint')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_compulsory')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default industry-standard regulatory categories
        DB::table('credential_categories')->insert([
            [
                'code' => 'operating_license',
                'name' => 'Operating License (AHPC / HEFRA)',
                'issuing_authority_hint' => 'Health Facilities Regulatory Agency (HEFRA) / AHPC Ghana',
                'description' => 'Mandatory statutory operating licence for clinical and pathology premises.',
                'is_compulsory' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'accreditation',
                'name' => 'International Accreditation (ISO 15189 / SADCAS / SANAS / CAP)',
                'issuing_authority_hint' => 'SADCAS / SANAS / CAP / KENAS / GSA',
                'description' => 'Medical laboratories standard accreditation for analytical competence and quality.',
                'is_compulsory' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'iso_certification',
                'name' => 'Quality Management Certification (ISO 9001 / ISO 13485)',
                'issuing_authority_hint' => 'Ghana Standards Authority / BSI / SGS',
                'description' => 'Quality management systems certification across specimen logistics and processes.',
                'is_compulsory' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'personnel_license',
                'name' => 'Chief Pathologist / Medical Director License',
                'issuing_authority_hint' => 'Medical and Dental Council Ghana / AHPC',
                'description' => 'Annual practice licence of the supervising consultant pathologist.',
                'is_compulsory' => false,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_categories');
    }
};
