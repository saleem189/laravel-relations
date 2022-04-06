<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    


    use HasFactory;

     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */

    protected $guarded=[];

    /**
     * this Project has many users here we will define relationship
     */

     public function users()
     {
         return $this->hasMany(User::class);
     }

     /**
      * this project belong to many users 
      * using Pivot table
      */
     public function pivot_users()
     {
         return $this->belongsToMany(User::class);
     }
     

     public function tasks()
     {
         /**
          * if we make relationship with .. hasmany()
          * it will give us Exception
          * Unknown Column 'tasks.project_id' it will not work because hasmany work 
          * with ID's of Model
          * here we will define hasManyThrough Relationship to make relation

          * we will pronounce this realtionship from left to right
          * Project hasMany Relation with Task::class Through User::class
          * hasmanythrough will return Collection of task  eg(in array)
          */
         return $this->hasManyThrough(Task::class,User::class);
     }

     public function task(){
         /**
          * hasOneThrough() will return the single task object
          */
         return $this->hasOneThrough(Task::class,User::class);
     }


}
/**
 * Has-Many_through  Relationship 
 * Suppose we have a model
 * Project
 * User
 * Task
 * 
 * User belongs to Poject
 * Task belongs to User 
 * 
 * Simple Words This->Project has many User
 * This->User has many Tasks
 * 
 * so in models and their Specific Model relation Columns
 * User:    project_id
 * Task:    user_id  
 * 
 * suppose we want want to implement Project->tasks relationship using intermediate
 * table which is User Model/Table
 * 
 * eg: we want to fetch Task related to the Project using Project relation method
 * like: $project->Tasks
 * 
 * we dont have column relationship in Task and Project. So how we can implemrnt this
 * We will us hasmanyThrough relationship here ..these are the complex relationships
 * 
 * Now we will create required Models to perfom this realtionship
 */


 /**
  * right Now One user is attach to One Project at a time and this Project may
  * may have many users.
  * So when One user Belongs to many Projects and Project may have Many Users
  * then it is many to many relationship
  * in that case we will use Pivot table 3rd intermediate table  for realtionship between user and project -- [HasMany()]
  * pivot table name: project_user: 
  *                             - project_id
  *                             - user_id
  *
  *
  *
  *
  *
  *
  *
  * 
  */
