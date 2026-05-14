<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso | Matriz SEDES</title>
    <link rel="stylesheet" href="{{ asset('css/sedes.css') }}">
</head>
<body>
    <section class="login-page">
        <form class="login-card" action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="login-logo">
                <img src="{{ asset('assets/img/LOGO_circulo_SEDES.png') }}" alt="Logo SEDES">
                <img src="{{ asset('assets/img/Logo_gobernacion_cuadrado.png') }}" alt="Gobernación">
            </div>
            <div class="login-title">
                <h1>SEDES</h1>
                <p>Matriz de Seguimiento<br>Unidad de Proyectos y Planificación</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="form-group">
                <label class="form-label" for="username">Usuario</label>
                <input class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Usuario123" required autofocus>
                @error('username')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" id="password" type="password" name="password" placeholder="Ingrese su Contraseña" required>
                @error('password')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Ingresar al Sistema</button>
            <p class="muted" style="text-align:center;margin-top:16px;font-size:13px">Olvidó su Contraseña?</p>
        </form>
    </section>
</body>
</html>
