@extends('layouts.panel')
@section('title', 'Ingresar')
@section('content')
<div class="row justify-content-center"><div class="col-md-5">
  <div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h4 mb-4">Iniciar sesión</h1>
    <form method="POST" action="{{ route('login.store') }}">@csrf
      <div class="mb-3"><label class="form-label" for="email">Correo electrónico</label>
        <input required autofocus id="email" type="email" name="email" class="form-control" value="{{ old('email') }}"></div>
      <div class="mb-3"><label class="form-label" for="password">Contraseña</label>
        <input required id="password" type="password" name="password" class="form-control"></div>
      <button class="btn btn-primary w-100">Ingresar</button>
    </form>
  </div></div>
</div></div>
@endsection
