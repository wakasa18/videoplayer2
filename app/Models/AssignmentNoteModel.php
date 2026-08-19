<?php
namespace App\Models;
use CodeIgniter\Model;
class AssignmentNoteModel extends Model
{
    protected $table='assignment_notes';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $allowedFields=['assignment_id','content','is_pinned','created_at','updated_at'];
    protected $useTimestamps=true;
    protected $dateFormat='datetime';
    public function forAssignments(array $ids): array
    {
        $ids=array_values(array_filter(array_map('intval',$ids)));
        if(!$ids)return [];
        $rows=$this->whereIn('assignment_id',$ids)->orderBy('is_pinned','DESC')->orderBy('created_at','DESC')->findAll();
        $group=[];foreach($rows as $r){$group[(int)$r['assignment_id']][]=$r;}return $group;
    }
}
