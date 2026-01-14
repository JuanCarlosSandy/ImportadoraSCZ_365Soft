@extends('auth.contenido')

@section('login')

<div class="content" id="app">
  <!-- Logo en la esquina inferior derecha -->
  <div class="logo-container">
    <img src="{{ asset('img/logoPrincipal.png') }}" alt="Logo" class="login-logo">
  </div>

  <!-- Formulario central -->
  <div class="login-box">
    <h1 class="login-title">Iniciar sesión</h1>
    
    <form class="formulario" method="POST" action="{{ route('login')}}">
      {{ csrf_field() }}
      
      <!-- Input Usuario -->
      <div class="container-input">
        <input type="text" value="{{old('usuario')}}" name="usuario" id="usuario" class="input-field" placeholder="Usuario" autocomplete="off">
        <div class="message">
          {!!$errors->first('usuario','<span class="invalid-feedback">El campo Usuario es obligatorio.</span>')!!}
        </div>
      </div>
      
      <!-- Input Contraseña con ojito -->
      <div class="container-input password-container">
        <input type="password" name="password" id="password" class="input-field" placeholder="Contraseña">
        <button type="button" id="togglePassword" class="toggle-password">
          <i class="fa-solid fa-eye" id="eyeIcon"></i>
        </button>
        <div class="message">
          {!!$errors->first('password','<span class="invalid-feedback">El campo Contraseña es obligatorio</span>')!!}
        </div>
      </div>
      
      <!-- Botón Iniciar sesión -->
      <div class="container-input btn-container">
        <button type="submit" class="btn-ingresar">Iniciar sesión</button>
      </div>
    </form>
    
    <!-- Nombre de la tienda -->
    <div class="store-name">KID TOYS</div>
    
    <!-- Frase motivadora -->
    <div class="motivational-phrase">¡Los mejores juguetes para tu hogar!</div>
    
    <!-- Aviso de credenciales -->
    <div class="credentials-notice">
      <i class="fa-solid fa-circle-info"></i>
      Si olvidó sus credenciales, comuníquese con el administrador
    </div>
  </div>
</div>

<script>
  // Funcionalidad del ojito para mostrar/ocultar contraseña
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePassword.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';

    // Cambiar clase del ícono
    eyeIcon.classList.toggle('fa-eye');
    eyeIcon.classList.toggle('fa-eye-slash');
  });
</script>

<script>
  @if($errors->has('password'))
    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('password').value = '';
    });
  @endif
</script>

@endsection