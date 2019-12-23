<?php

use Fureev\Social\Concerns\UUID;
use Fureev\Social\Services\SocialAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialAccountsTable extends Migration
{
    use UUID;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('social_accounts', static function (Blueprint $table) {
            static::columnUUID($table, 'id')->primary();
            $table->{SocialAccountService::$userKeyType}('user_id');
            $table->string('provider_user_id');
            $table->string('provider')->index();
            $table->jsonb('raw')->default('[]');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['provider_user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_accounts');
    }
}
