<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\CodeUnit\FunctionUnit;

class Task extends Model
{
    use HasFactory;

     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $guarded=[];

    /**
     * this Task belongs to User Model.
     * we define User has Many Tasks in User Module
     * this is reverse Relation of it
     */

     public function user(){
         return $this->belongsTo(User::class);
     }
}
