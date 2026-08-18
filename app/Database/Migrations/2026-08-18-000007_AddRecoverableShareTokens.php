<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecoverableShareTokens extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS token_ciphertext TEXT');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE important_file_shares DROP COLUMN IF EXISTS token_ciphertext');
    }
}
