<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    protected $fillable = [
        'original_filename',
        'compressed_data',
        'original_size',
        'compressed_size',
        'compression_ratio',
    ];
}
