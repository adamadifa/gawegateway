<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
