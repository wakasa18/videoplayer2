<?php
namespace App\Models;
use CodeIgniter\Model;
class AssignmentTemplateModel extends Model
{
    protected $table='assignment_templates';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $allowedFields=['name','title','description','priority','recurrence','subject_id','due_time','reminder_minutes_before','link_url','created_at','updated_at'];
    protected $useTimestamps=true;
    protected $dateFormat='datetime';
}
