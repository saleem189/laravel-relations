<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * insted of Model we will extend it from Pivot
 */
class PostTag extends Pivot
{
    use HasFactory;

    protected $table= 'post_tag';
    /**
     * Handeling Events in Model which are pre define in model ..every 
     * model have predefine LIFE CYCLE  events or Function which are triggried when some changes are made 
     * in Pivot 
     * created
     * creating
     * deleted
     * deleting
     * and Sync  perform all events ..create ,del or update
     * 
     * we will override boot method of model to handle these events acoording to our will
     *  
     */

     public static function boot()
     {
         parent::boot();

         /**
          * creating Created event 
          */

          static::created(function($item){
                dd('Created Event is called ', $item);
          });

          static::deleted(function($item){
              dd('deleted event is called', $item);
          });


     }
}
