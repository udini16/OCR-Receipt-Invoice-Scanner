<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{   
    protected $table = 'receipts';
    use HasFactory;

    public function lineItems()
    {
        return $this->hasMany(LineItem::class);
    }
}
