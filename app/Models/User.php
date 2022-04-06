<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'project_id'

        /**
         * we are adding Project id here for Projects relationship
         * 
         */
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * this User has one Address Relationship
     */

    public function address()
    {
        return $this->hasOne(Address::class); //For single relation ONE O ONE
    }

    /**
     * This User has many Addresses
     */

    public function addresses()
    {
        return $this->hasMany(Address::class); // For One to Many Relation
    }
    
    /**
     * this User has many Posts
     */
    public function post(){
        return $this->hasMany(Post::class);   //for many to many
    }

    /**
     * this user belongs to Project Model ---one project
     * reverse is define here
     */

    public function project(){
        return $this->belongsTo(Project::class);
    }

    /**
     * here we are defining relation with many-to-many relationship
     * by using pivot table ---this user belongs to many projects
     * reverse is define here 
     */

    public function pivot_projects()
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * this user has many Tasks
     */
    public function tasks(){
        return $this->hasMany(Task::class);
    }
}
