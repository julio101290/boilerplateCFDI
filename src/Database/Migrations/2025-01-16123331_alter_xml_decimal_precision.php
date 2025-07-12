<?php
// Adaptado por julio101290

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterXmlDecimalPrecision extends Migration
{
    public function up()
    {
        $fields = [
            'total' => [
                'name'       => 'total',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
            'base16' => [
                'name'       => 'base16',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
            'totalImpuestos16' => [
                'name'       => 'totalImpuestos16',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
            'base8' => [
                'name'       => 'base8',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
            'totalImpuestos8' => [
                'name'       => 'totalImpuestos8',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
            'tasaExenta' => [
                'name'       => 'tasaExenta',
                'type'       => 'DECIMAL',
                'constraint' => '18,5',
                'null'       => true,
            ],
        ];

        $this->forge->modifyColumn('xml', $fields);
    }

    public function down()
    {
        $fields = [
            'total' => ['name' => 'total', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
            'base16' => ['name' => 'base16', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
            'totalImpuestos16' => ['name' => 'totalImpuestos16', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
            'base8' => ['name' => 'base8', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
            'totalImpuestos8' => ['name' => 'totalImpuestos8', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
            'tasaExenta' => ['name' => 'tasaExenta', 'type' => 'DECIMAL', 'constraint' => '18', 'null' => true],
        ];

        $this->forge->modifyColumn('xml', $fields);
    }
}