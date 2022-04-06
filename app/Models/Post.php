<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model

{

    use HasFactory;
    protected $fillable=[
        'user_id',
        'title'
    ];
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name'=>'Guest User'
        ]); // withdefault is also used ..can pass default name..the Default name for User if it has no ID or Guest User and if we remove Optional Method in Post
        // post.blade than it will also work
    }


   
    /**
     * Many to Many Relationship
     * supose we have 
     * post -may have many tags
     * tags -may have many posts
     * to implement many to many relationship
     * we use 3rd Table
     * Pivot Table
     * options of Pivot table belongsToMany(modelnameOfRelation, 'CurrentModelname_RelationalModelname')
     * Migration or Table Creating Commant                                      Naming Convention
     * php artisan make:migration create_post_tag_table --create=post_tag
     *                            Migration Name                 Table Name
     * 
     * in Pivot table we dont use save mathod we use Attach eg($tag =Tag::first();
     * $post= Post::first();
     * $post->tags()->attach($tag);
     * )
     * Detach is used to de-attach any tag from post
     * to show additional field which you add in migrations in database 
     * we will add withPivot('column_name')
     * we can also use Your_name Model class which will extend from Pivot Model 
     * so we will use PostTag model name
     * if you want to Handle events on it 
     * we also add some changes in Relation define below
     * 
     * Before modification just simple without using Pivot model
     * public function tags(){
     *   return $this->belongsToMany(Tag::class)->withTimestamps()->withPivot('status');
     * }
     * 
     * After Modification and making Pivot Model of PostTag Model ---we using ->using() method of Pivot Predefine Function 
     * public function tags(){
     *         return $this->belongsToMany(Tag::class)->using(PostTag::class)
     *         ->withTimestamps()
     *         ->withPivot('status');
     * }
     */ 

    //  public function tags(){
    //      return $this->belongsToMany(Tag::class)->withTimestamps()->withPivot('status');
    //  }

     public function tags(){
        return $this->belongsToMany(Tag::class)->using(PostTag::class)
        ->withTimestamps()
        ->withPivot('status');
    }
}


