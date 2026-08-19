<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpgradeAssignmentsModule extends Migration
{
    public function up()
    {
        $this->forge->addColumn('assignments', [
            'subject_id' => ['type' => 'INT', 'null' => true],
            'recurrence_series_id' => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true],
            'next_occurrence_id' => ['type' => 'INT', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'archived_at' => ['type' => 'DATETIME', 'null' => true],
            'reminder_minutes_before' => ['type' => 'INT', 'default' => 1440, 'null' => false],
            'custom_reminder_at' => ['type' => 'DATETIME', 'null' => true],
            'template_id' => ['type' => 'INT', 'null' => true],
        ]);

        $this->db->query("UPDATE assignments SET status = 'to_do' WHERE status = 'pending'");
        $this->db->query("UPDATE assignments SET completed_at = updated_at WHERE status = 'done' AND completed_at IS NULL");

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'instructor' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#42E9FF'],
            'schedule' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'semester' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_archived' => ['type' => 'BOOLEAN', 'default' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('assignment_subjects', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_done' => ['type' => 'BOOLEAN', 'default' => false],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['assignment_id', 'sort_order']);
        $this->forge->addForeignKey('assignment_id', 'assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('assignment_subtasks', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'content' => ['type' => 'TEXT'],
            'is_pinned' => ['type' => 'BOOLEAN', 'default' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['assignment_id', 'created_at']);
        $this->forge->addForeignKey('assignment_id', 'assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('assignment_notes', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'important_file_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assignment_id', 'important_file_id']);
        $this->forge->addForeignKey('assignment_id', 'assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('important_file_id', 'important_files', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('assignment_file_links', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'medium'],
            'recurrence' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'subject_id' => ['type' => 'INT', 'null' => true],
            'due_time' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'reminder_minutes_before' => ['type' => 'INT', 'default' => 1440],
            'link_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('assignment_templates', true);

        $this->db->query("CREATE INDEX IF NOT EXISTS idx_assignments_queue_v2 ON assignments (deleted_at, archived_at, status, due_date)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_assignments_subject_id ON assignments (subject_id)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_assignments_completed_at ON assignments (completed_at)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_assignments_recurrence_series ON assignments (recurrence_series_id)");

        $this->migrateLegacySubjects();
        $this->seedDefaults();
        $this->migrateLegacyNotes();
    }

    private function migrateLegacySubjects(): void
    {
        $rows = $this->db->table('assignments')->select('subject')->where('subject IS NOT NULL', null, false)->where('subject <>', '')->groupBy('subject')->get()->getResultArray();
        foreach ($rows as $row) {
            $name = trim((string) $row['subject']);
            if ($name === '') {
                continue;
            }
            $subject = $this->db->table('assignment_subjects')->where('name', $name)->get()->getRowArray();
            if (! $subject) {
                $this->db->table('assignment_subjects')->insert([
                    'name' => mb_substr($name, 0, 100), 'color' => '#42E9FF',
                    'is_archived' => false, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $subjectId = $this->db->insertID();
            } else {
                $subjectId = $subject['id'];
            }
            $this->db->table('assignments')->where('subject', $row['subject'])->where('subject_id', null)->update(['subject_id' => $subjectId]);
        }
    }

    private function seedDefaults(): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [
            ['Written Assignment', 'Written Assignment', 'Complete the written activity and review formatting before submission.', 'medium'],
            ['Presentation', 'Presentation', 'Prepare slides, speaker notes, and rehearse the final presentation.', 'high'],
            ['Quiz Review', 'Quiz Review', 'Review the lesson, summarize key ideas, and answer practice questions.', 'medium'],
            ['Research Paper', 'Research Paper', 'Gather sources, draft the paper, review citations, and submit the final copy.', 'high'],
            ['Capstone Deliverable', 'Capstone Deliverable', 'Complete the assigned capstone milestone and prepare supporting files.', 'high'],
        ];
        foreach ($rows as [$name, $title, $description, $priority]) {
            if (! $this->db->table('assignment_templates')->where('name', $name)->countAllResults()) {
                $this->db->table('assignment_templates')->insert([
                    'name' => $name, 'title' => $title, 'description' => $description,
                    'priority' => $priority, 'reminder_minutes_before' => 1440,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function migrateLegacyNotes(): void
    {
        $rows = $this->db->table('assignments')->select('id, notes_log')->where('notes_log IS NOT NULL', null, false)->where('notes_log <>', '')->get()->getResultArray();
        foreach ($rows as $row) {
            if ($this->db->table('assignment_notes')->where('assignment_id', $row['id'])->countAllResults()) {
                continue;
            }
            foreach (preg_split('/\r\n|\r|\n/', (string) $row['notes_log']) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $this->db->table('assignment_notes')->insert([
                        'assignment_id' => $row['id'], 'content' => mb_substr($line, 0, 2000),
                        'is_pinned' => false, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('assignment_file_links', true);
        $this->forge->dropTable('assignment_notes', true);
        $this->forge->dropTable('assignment_subtasks', true);
        $this->forge->dropTable('assignment_templates', true);
        $this->forge->dropTable('assignment_subjects', true);
        $this->forge->dropColumn('assignments', [
            'subject_id', 'recurrence_series_id', 'next_occurrence_id', 'completed_at',
            'archived_at', 'reminder_minutes_before', 'custom_reminder_at', 'template_id',
        ]);
    }
}
