<?php

use App\Models\Address;
use App\Models\Post;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

use Faker\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Mpdf\Mpdf;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();


Route::get('/pdf', function () {

    $projects = Project::with('users')->find(18);
    $p =$projects->pivot_users->count();
    // dd($p);
   // $projects = Project::with('users')->find(18)->toArray();

    // return view('posts',compact('projects'));
    // return $projects->users;
    // $posts = Post::with('user')->get();
    // dd($projects->pivot_users);
     return view('pdf',compact('projects','p'));
    
    /**
     * saving Collection to array then showing array
     */
    
//  $array = array();
//  foreach($projects as $p)
// {
//     // dd($projects->pivot_users[1]->name);
//     // dd($p);
//     foreach($p->pivot_users as $user)
//     {
//         // dd($user->name);
//     $array = array('post'=>$p->title,'user'=>$user->name);
//     // dd($user->name);
// }
// }
// dd($array);

   
    


    // $mpdf = new Mpdf();
    // $mpdf->WriteHTML($html);
    // $mpdf->Output();
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/users', function () {

    /**
     * creating user with faker
     * $users= User::factory()->create(); 
     */
    
     /**
      * create 5 users instances means 5 reocrds / users
      * User::factory()->count(5)->create();
      */

        //  $users= User::factory()->count(5)->create();
    


    // \App\Models\Address::create([
    //     'user_id' => 5,
    //     'country' => 'pakistan',
    //     'city' => 'Mumbai',
    //     'zip_code' => 74200,
    // ]);
    $users = User::get();
    return view('index',compact('users'));

    
});

Route::get('/tags', function () {
    $tags = Tag::with('posts')->get();
    // dd($tags);
    return view('tags',compact('tags'));
    
});

Route::get('/pivot', function () {

    /**
     * first we create new record 
     * 
     * Tag::create([
     * 'name' =>'laravel',
     * ]);
     * 
     * Tag::create([
     * 'name' =>'php',
     * ]); 
     *  
     * Tag::create([
     * 'name' =>'react',
     * ]);  
     * Tag::create([
     * 'name' =>'java',
     * ]);
     * 
     * Tag::create([
     * 'name' =>'python',
     * ]); 
     *  
     * Tag::create([
     * 'name' =>'Andriod',
     * ]); 
     */



    

        // $tag = Tag::first();
        // dd($tag->id);
        
        // $post= Post::with('tags')->first();
        // $post->tags()->attach(3);

    /**
     * to attach a value to column
     * we will use id with array for value insertion
     *  $post =Post::first()
     * $post->tags()->attach([
     *      1 => [
     *              'status'=>'approved'
     *   ]
     * ])
     * 
     * 
     * To make it Dynamic according to tag
     * 
     * $tag = Tag::first();
     * $post =Post::first()
     * $post->tags()->attach([
     *      $tag->id => [
     *              'status'=>'approved'
     *   ]
     * ])
     */

     $post=Post::first();
    //  dd($post->tags->first());
    //  $post->tags()->attach([
    //      5 => [
    //          'status' => 'approved'
    //      ]
    //  ]);
    //   $post->tags()->sync([
    //          5 => [
    //              'status' => 'approved'
    //          ]
    //      ]);

    // $post->tags()->detach(2);

    /**
     * To show Pivot Table Realtion we will use 
     * $post =Post::first();
     * dd($post->tags);
     */    
        // $post =Post::first();
        // dd($post->tags);

    /**
     * to return Tags through relation 
     * we will return first tag from tags collection
     * $post->tags->first(); 
     * we can also show Pivot created at here 
     * by $post->tags->first()->pivot->created_at
     *  
     */
    
    //  dd($post->tags->first()->pivot->created_at);

    /**
     * attach is used to attach one model to another
     * $post->tags()->attach($tag);
     * 
     * detach is use to de-attach one model from  another model
     * $post->tags()->detach([2]);
     */

        // $post= Post::first();
        // $post->tags()->attach($tag);

    

    


    /**
     * To update Record in Pivot Table
     * first detach() is use to remove all than
     * attach() to update it with new records
     * multiple records/tags can be attach to it
     * $post->tags()->detach();
     * $post->tags()->attach([2,4]);
     */

    // $post->tags()->detach();
    // $post->tags()->attach([2,4]);
    
     /**
      * Better Approch to Update Record in Pivot is SYNC Method
      * sync() function
      * $post->tags()->sync([tag_id,tag_id])
      */

        // $post->tags()->sync([4,5]);
        // $post->tags()->attach(0);


    
    
    

    /**
     * we can also attach multiple tags
     * $post->tags()->attach([1,2,3,4,5]);
     */

   

    /**
     * Showing Posts and Tags with User in Template
     */
        $posts = Post::with(['user','tags'])->get();
        
        return view('pivot', compact('posts'));


    
});
Route::get('/usersPost', function () {
    
     
    /**
     * we will dislay all Users with and without Post
     * $users = User::with('post')->get();
     */
    
     // $users = User::with('post')->get();

    /**
     * Only Fetch Users who have some Posts
     * $users = User::has('post')->with('post')->get();
     */


     // $users = User::has('post')->with('post')->get();

    /**
     * only show those who have 2 or more Posts (2=>)
     * $users = User::has('post','>=', 2)->with('post')->get();
     */

    // $users = User::has('post','>=', 2)->with('post')->get();

    /**
     * only show user where has (required keyword) specific keyword
     * $users = User::whereHas('post', function($query){
     * $query->where('title', 'like','%associated%');
     * })->with('post')->get();
     * 
     */

    // $users = User::whereHas('post', function($query){
    //     $query->where('title', 'like','%associated%');
    // })->with('post')->get();

    /**
     * fetch users who has not created any POst
     * $users = User::doesntHave('post')->with('post')->get();
     */

    // $users = User::doesntHave('post')->with('post')->get();


    /**
     * Assingning Post manualy to Users 
     * 
     * $users[0]->post()->create([
     *  'title' => 'this Post is associated to 1st User'
     * ]);
     * $users[1]->post()->create([
     *  'title' => 'this Post is associated to 1st User'
     * ]);
     */


 

    $users = User::with('post')->get();
    // $users[0]->post()->create([
    //     'title' => 'this Post is associated to 1st User'
    // ]);
    // $users[1]->post()->create([
    //     'title' => 'this Post is associated to 2nd User '
    // ]);



    return view('usersPost',compact('users'));

    
});
Route::get('/usersdata', function () {
    // $user = User::factory()->create();
    // $user->address()->create([
    //     'country' => 'India',
    //     'city' => 'Kolkata',
    //     'zip_code' => 1111,
    // ]);
    

  
    // $address= Address::with('user')->get(); // It shorts the SQl Query to minimum
    $users = User::with('addresses')->get();
    // $users[0]->addresses()->create([
    //     'country' =>'USA',
    //     'city' =>'New York',
    //     'zip_code' =>02134,
    // ]);    
//    return view('index',compact('address'));
return view('index',compact('users'));


    
});
Route::get('/createtag', function () {
    Tag::create([
        'name'=> 'Ruby On Rails'
    ]);
    Tag::create([
         'name' =>'laravel',
         ]);
         
         Tag::create([
         'name' =>'php',
         ]); 
          
         Tag::create([
         'name' =>'react',
         ]);  
         Tag::create([
         'name' =>'java',
         ]);
         
         Tag::create([
         'name' =>'python',
         ]); 
          
         Tag::create([
         'name' =>'Andriod',
         ]); 
    
});

Route::get('/post', function () {
    /**
     * creating Post without Regesterd users means as Guest
     * we Define a default Guest in Post Model
     * 
     * Post::create([
     * 'title' => 'Post of Guest 2 User saved without user ID'
     * ]);
     * Post::create([
     * 'title' => 'Post of Guest 1 User saved without user ID'
     * ]);
     */


    // Post::create([
        
    //     'title' => 'Post of Guest 2 User saved without user ID'
    // ]);
    // Post::create([
        
    //     'title' => 'Post of Guest 1 User saved without user ID'
    // ]);
    // $user = User::factory()->create();
    // $user->post()->create([
    //         'title' => 'Post 2 '
            
    //      ]);
    $posts = Post::with('user')->get();
    // dd($posts);
    
    return view('posts',compact('posts'));

    
});

/**
 * Route for Project to Show Project and required Relations
 */
Route::get('/projects', function () {
    /**
     * Creating Projects to Populate data
     * 
     * Creating User and than assiging Project to it 
     * 
     * Creating Task and then assigning User to it
     * 
     * $projects =Project::create([
     * 'title' => 'laravel Relations'
     * ]);
     */

    /**
     * The Tree Looks like this 
     * We have 1 Project and 2 users and 4 tasks
     * so Project has 2 Users 
     * and User has 4 Tasks.
     * for User 1 we have Task 1 and Task 2
     * for User 2 we have Task 3 and Task 4
     */

    //  $projects =Project::create([
    //      'title' => 'Project 2'
    //  ]);

    //  $user1 = User::create([
    //      'name' =>'User 3',
    //      'email'=> 'user3@example.com',
    //      'password' => Hash::make('password'),
    //      'project_id' => $projects->id
    //  ]);
    //  $user2 = User::create([
    //     'name' =>'User 4',
    //     'email'=> 'user4@example.com',
    //     'password' => Hash::make('password'),
    //     'project_id' => $projects->id
    // ]);
    // $user3 = User::create([
    //     'name' =>'User 5',
    //     'email'=> 'user5@example.com',
    //     'password' => Hash::make('password'),
    //     'project_id' => $projects->id
    // ]);

    // $task1 =Task::create([
    //     'user_id' => $user1->id,
    //     'title' => 'Task 4 for User 3 for Project 2'

    // ]);

    // $task2 =Task::create([
    //     'user_id' => $user1->id,
    //     'title' => 'Task 4 for User 3 for Project 2'

    // ]);
    // $task3 =Task::create([
    //     'user_id' => $user2->id,
    //     'title' => 'Task 5 for User 4 for Project 2'

    // ]);
    // $task4 =Task::create([
    //     'user_id' => $user3->id,
    //     'title' => 'Task 6 for User 5 for Project 2'

    // ]);

    // $task4 =Task::create([
    //     'user_id' => $user2->id,
    //     'title' => 'Task 4 for User 2 for Project 1'

    // ]);

    /**
     * Now we will return Project where id is 1
     * $project = Project::find(1);
     * return $project;
     */
    $project = Project::find(1);
    
    /**
     * get me users belong to this Project
     * return $project->users;
     */

    // return $project->users;

    /**
     * get me Tasks Belong to First User
     */
    // return $project->users[1]->tasks;

    /**
     * get me all tasks that belong to this Project
     * 
     * we Define hasManyThrough relationship in Model 
     * if we define hasMany() it will not work
     */

    return $project->tasks;

});

Route::get('/pivotprojects', function () {

     
    /**
     * here we will create and assign projects to users 
     * 
     */
    
    /*
     
    $project1= Project::create([
        'title' => 'project A'
    ]);
    $project2= Project::create([
        'title' => 'Project B'
    ]);
    $project3= Project::create([
        'title' => 'project C'
    ]);

    $user1=User::create([
        'name'=> 'pivot table User A',
        'email' => 'userA@gexample.com',
        'password' => Hash::make('password'),
        'project_id' => 1111

    ]);

    $user2=User::create([
        'name'=> 'pivot table User B',
        'email' => 'userB@gexample.com',
        'password' => Hash::make('password'),
        'project_id' => 1111

    ]);
    $user3=User::create([
        'name'=> 'pivot table User C',
        'email' => 'userC@gexample.com',
        'password' => Hash::make('password'),
        'project_id' => 1111
        

    ]);

    $project1->pivot_users()->attach($user1);
    $project1->pivot_users()->attach($user2);
    $project1->pivot_users()->attach($user3);
    $project2->pivot_users()->attach($user1);
    $project2->pivot_users()->attach($user2);
    $project3->pivot_users()->attach($user1);
    $project3->pivot_users()->attach($user2);
    */

    /**
     * get this project users
     * $project1 = Project::find(18);
     * return $project1->pivot_users; 
     */
    // $project1 = Project::find(18);
    // return $project1->pivot_users;

    /**
     * get this user project's
     */

     $user = User::find(13);
         return $user->pivot_projects;
    
});
