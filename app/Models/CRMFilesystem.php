<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CRMFilesystem extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "company";
    protected $table = "crm_filesystem";
}
