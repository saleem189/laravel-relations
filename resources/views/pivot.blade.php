@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
           <div class="card">
             @foreach($posts as $post)
             <h2>{{$post->title}}</h2>
             
             <p>{{$post->user->name}}</p>

             <ul>
                 @foreach($post->tags as $tag)
                 <li>{{$tag->name}}  {{$tag->pivot->created_at}}</li>
                 Status: {{$tag->pivot->status}}
                 @endforeach
             </ul>
            
             @endforeach
           </div>
        </div>
    </div>
</div>
@endsection