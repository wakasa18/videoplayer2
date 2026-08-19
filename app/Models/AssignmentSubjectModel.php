<?php
namespace App\Models;
use CodeIgniter\Model;
class AssignmentSubjectModel extends Model
{
    protected $table='assignment_subjects';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $allowedFields=['name','code','instructor','color','schedule','semester','is_archived','created_at','updated_at'];
    protected $useTimestamps=true;
    protected $dateFormat='datetime';
    public function active(): array { return $this->where('is_archived',false)->orderBy('name','ASC')->findAll(); }
}
