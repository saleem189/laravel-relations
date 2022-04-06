@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
           <div class="card">
               @foreach($users as $user)
              <h2>{{$user->name}}</h2>
               Mail:{{$user->email}}
               @foreach($user->addresses as $address)
               Country: {{$address->country}}
               City: {{$address->city}}
               Zip Code: {{$address->zip_code}}
               @endforeach
               @endforeach
           </div>
        </div>
    </div>
</div>
@endsection
