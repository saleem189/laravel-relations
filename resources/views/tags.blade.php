@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
           <div class="card">
             @foreach($tags as $tag)
             <h2>{{$tag->name}}</h2>
             
             

             <ul>
                 @foreach($tag->posts as $post)
                 <li>{{$post->title}}</li>
                 @endforeach
             </ul>
            
             @endforeach
           </div>
        </div>
    </div>
</div>
@endsection