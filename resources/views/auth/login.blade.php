@extends('auth.contenido')

@section('login')


<div class="content" id="app">
  <div class="container-login">
    <!-- Columna izquierda -->
    <div class="left">
      <img src="{{ asset('img/logoPrincipal.png') }}" alt="Logo" class="login-logo">
      <h2 class="welcome-title">¡Bienvenido!</h2>
      
      <p class="welcome-text">
        IMPORTACIONES SEMO <br>
        Los mejores vasos!!
      </p>
    </div>

    <!-- Columna derecha -->
    <div class="right">
      
      <p class="login-frase">
        Iniciar sesión <br>
      </p>
      <form class="formulario" method="POST" action="{{ route('login')}}">
        {{ csrf_field() }}
        <div class="container-input">
          <input type="text" value="{{old('usuario')}}" name="usuario" id="usuario" class="input-texto-arriba" placeholder="Usuario">
          <div class="message">
            {!!$errors->first('usuario','<span class="invalid-feedback">El campo Usuario es obligatorio.</span>')!!}
          </div>
        </div>
        <div class="container-input">
          <input type="password" name="password" id="password" class="input-texto-arriba" placeholder="Contraseña">
          <div class="message">
            {!!$errors->first('password','<span class="invalid-feedback">El campo Contraseña es obligatorio</span>')!!}
          </div>
        </div>
        <div class="container-input">
          <button type="submit" class="btn-ingresar">Iniciar sesión</button>
        </div>
      </form>
    </div>
  </div>
<script>
  // Escuchamos el evento input en los campos de input
  const inputs = document.querySelectorAll('.input-texto-arriba');

  inputs.forEach(input => {
    input.addEventListener('focus', () => {
      // Agregamos la clase 'texto-arriba' cuando el campo tiene el foco
      input.classList.add('texto-arriba');
    });

    input.addEventListener('blur', () => {
      // Removemos la clase 'texto-arriba' solo si el campo está vacío
      if (input.value) {
        input.classList.remove('texto-arriba');
      }
    });

    // Verificamos si el campo ya tiene un valor inicial al cargar la página
    if (input.value) {
      input.classList.add('texto-arriba');
    }
  });
</script>

<script>
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