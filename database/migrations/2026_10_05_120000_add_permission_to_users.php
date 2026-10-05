<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 50)->nullable()->after('id')->comment('姓名');
            $table->unsignedTinyInteger('permission')->default(0)->after('name')->comment('0用户 10审核 20管理员');
        });

        // 初始管理员
        DB::table('users')->updateOrInsert(
            ['id' => '32501010035'],
            ['permission' => 20]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name', 'permission']);
        });
    }
};
