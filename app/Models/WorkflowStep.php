<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model {
    protected $table = 'workflow_steps';
    protected $fillable = ['request_id','actor_role','actor_id','action','comment','order'];

    public function request() { return $this->belongsTo(WorkflowRequest::class, 'request_id'); }
}