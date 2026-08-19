<?php
namespace App\Models;
use CodeIgniter\Model;
class AssignmentSubtaskModel extends Model
{
    protected $table='assignment_subtasks';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $allowedFields=['assignment_id','title','is_done','sort_order','created_at','updated_at'];
    protected $useTimestamps=true;
    protected $dateFormat='datetime';
    public function forAssignments(array $ids): array
    {
        $ids=array_values(array_filter(array_map('intval',$ids)));
        if(!$ids)return [];
        $rows=$this->whereIn('assignment_id',$ids)->orderBy('sort_order','ASC')->orderBy('id','ASC')->findAll();
        $group=[];foreach($rows as $r){$group[(int)$r['assignment_id']][]=$r;}return $group;
    }
}
