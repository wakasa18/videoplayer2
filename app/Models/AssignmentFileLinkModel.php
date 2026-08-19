<?php
namespace App\Models;
use CodeIgniter\Model;
class AssignmentFileLinkModel extends Model
{
    protected $table='assignment_file_links';
    protected $primaryKey='id';
    protected $returnType='array';
    protected $allowedFields=['assignment_id','important_file_id','created_at'];
    public function forAssignments(array $ids): array
    {
        $ids=array_values(array_filter(array_map('intval',$ids)));
        if(!$ids)return [];
        $rows=$this->select('assignment_file_links.*, important_files.title, important_files.original_filename, important_files.mime_type, important_files.file_size, important_files.file_extension')
            ->join('important_files','important_files.id=assignment_file_links.important_file_id','inner')
            ->whereIn('assignment_file_links.assignment_id',$ids)
            ->where('important_files.status','active')
            ->orderBy('assignment_file_links.id','DESC')->findAll();
        $group=[];foreach($rows as $r){$group[(int)$r['assignment_id']][]=$r;}return $group;
    }
}
