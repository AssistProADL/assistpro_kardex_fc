<?php

namespace AssistPro\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'c_usuario';
    protected $primaryKey = 'cve_usuario';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Assuming legacy table doesn't have created_at/updated_at

    // Allow mass assignment for testing if needed, or guard it
    protected $guarded = [];
}
