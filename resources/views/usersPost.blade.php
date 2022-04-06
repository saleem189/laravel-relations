@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
           <div class="card">
             @foreach($users as $user)
             <h2>{{$user->name}}</h2>
             
             @foreach($user->post as $post)
             <p>{{$post->title}}</p>
             @endforeach
             @endforeach
           </div>
        </div>
    </div>
</div>
@endsection



<p><code>optional($post->user)->name</code></p>  optinal is used when there id is null and you want to access a dara from talbe like Post og Guest it has no id save beacuse it is not registerd thats why
Shownig All users with their Posts if thay have no post Only User Name Will be shown to registered User
