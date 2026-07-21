<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


class AddTimestampsToPengajuanCuti extends Migration
{
    public function up()
    {
        $fields = [
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        $this->forge->addColumn('pengajuan_cuti', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pengajuan_cuti', ['created_at', 'updated_at']);
    }
}
