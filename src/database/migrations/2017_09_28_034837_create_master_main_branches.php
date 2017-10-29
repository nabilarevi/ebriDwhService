<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterMainBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        # Create Region
        Schema::create('master_region', function (Blueprint $table) {
            $table->string('code', 1)->index();
            $table->string('name');
            $table->timestamps();

            $table->primary(array('code'), 'master_region_primary_key');
        });

        # Create MAIN BRANCHES
        Schema::create('master_main_branch_code', function (Blueprint $table) {
            $table->string('code',1)->index();
            $table->integer('id')->index();
            $table->string('name');
            $table->string('description');
            $table->integer('order');
            $table->timestamps();

            $table->primary(array('code', 'id'), 'master_branches_code_primary_key');
            $table->foreign('code')->references('code')->on('master_region')
                ->onUpdate('cascade')->onDelete('cascade');
        });

        # Create BRANCHES
        Schema::create('master_branch_code', function (Blueprint $table) {
            $table->string('code',1)->index();
            $table->integer('mbranch')->index();
            $table->integer('id')->index();
            $table->integer('segment');
            $table->integer('flags');
            $table->string('description');
            $table->timestamps();

            $table->primary(array('code', 'mbranch', 'id'), 'master_offices_code_primary_key');

            $table->foreign('code')->references('code')->on('master_region')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('mbranch')->references('id')->on('master_main_branch_code')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('master_branch_code');
        Schema::drop('master_main_branch_code');
        Schema::drop('master_region');
    }
}
