@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
           <div class="card">
             <h2>{{$projects->title}}</h2>
             @foreach($projects->pivot_users as $user)
             <h5>{{$user->name}}</h5>
            @endforeach
            <h6>Total: {{$p}}</h6>
             
            
           </div>
        </div>
    </div>
</div>
@endsection

