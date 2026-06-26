<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table    = 'audit_logs';
    public $timestamps  = true;
    protected $fillable = ['team_id', 'activity', 'log_content', 'meta'];
}
