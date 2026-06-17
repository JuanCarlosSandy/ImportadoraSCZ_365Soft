@extends('auth.contenido')

@section('login')

<div class="login-wrapper" id="app">
  <!-- Panel izquierdo - Formulario -->
  <div class="login-left">
    <div class="login-content">
      <!-- Logo -->
      <div class="logo-container">
        <img src="{{ asset('img/logoPrincipal.png') }}" alt="Logo" class="login-logo">
      </div>

      <!-- Título -->
      <p class="login-subtitle">Bienvenido de nuevo</p>
      <h1 class="login-title">Iniciar Sesión</h1>

      <!-- Formulario -->
      <form class="login-form" method="POST" action="{{ route('login')}}">
        {{ csrf_field() }}
        
  <div class="form-group">
          <label for="usuario" class="form-label">Usuario</label>
          <div class="input-wrapper">
            <input 
              type="text" 
              value="{{old('usuario')}}" 
              name="usuario" 
              id="usuario" 
              class="form-input" 
              placeholder="Ingrese su usuario"
              autocomplete="off"
            >
            <span class="input-icon">
              <i class="fas fa-user"></i>
            </span>
          </div>
          @if($errors->has('usuario'))
            <span class="error-message">{{ $errors->first('usuario') }}</span>
          @endif
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Contraseña</label>
          <div class="input-wrapper">
            <input 
              type="password" 
              name="password" 
              id="password" 
              class="form-input" 
              placeholder="••••••••"
            >
            <span class="input-icon toggle-password" id="togglePassword">
              <i class="fas fa-eye" id="eyeIcon"></i>
            </span>
          </div>
          @if($errors->has('password'))
            <span class="error-message">{{ $errors->first('password') }}</span>
          @endif
        </div>

        <!-- Botón de envío -->
        <button type="submit" class="btn-login">
          <span>Iniciar Sesión</span>
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <!-- Footer -->
     <div class="login-footer">
      <p>
        BROKEN - Importadora de Productos<br>
        Más de 20 años al servicio de nuestros Clientes.
      </p>
    </div>
    </div>
  </div>

  <!-- Panel derecho - Imagen decorativa -->
  <div class="login-right">
    <div class="decorative-bg">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="shape shape-4"></div>
    </div>
    <div class="right-content">
      <div class="brand-text">
        <h2>Sistema de Gestión de Empresa</h2>
        <p>BROKEN IMPORTACIONES</p>
      </div>
    </div>
  </div>
</div>

<script>
  // Toggle password visibility
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  if (togglePassword) {
    togglePassword.addEventListener('click', function () {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      eyeIcon.classList.toggle('fa-eye');
      eyeIcon.classList.toggle('fa-eye-slash');
    });
  }

  // Clear password on error
  @if($errors->has('password'))
    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('password').value = '';
    });
  @endif
</script>

@endsection