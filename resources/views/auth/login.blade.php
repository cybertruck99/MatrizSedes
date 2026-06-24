@php
    $recoveryMode = session('recovery_mode') || $errors->has('recovery_username') || session('recovery_error') || session('recovery_success') || session('recovery_message');
@endphp
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
        @if($baseAdminNotice)
            <div class="base-admin-notice" role="dialog" aria-modal="true" aria-labelledby="base-admin-title">
                <div class="base-admin-card">
                    <h2 id="base-admin-title">Administrador base del sistema</h2>
                    <p>
                        Esta información se mostrará una sola vez. Guarde el usuario y la contraseña antes de continuar.
                        Esta cuenta sirve para el primer ingreso y luego deberá cambiar su usuario y contraseña.
                    </p>
                    <div class="base-admin-credentials">
                        <span>Usuario</span>
                        <strong>{{ $baseAdminNotice['username'] }}</strong>
                        <span>Contraseña</span>
                        <strong>{{ $baseAdminNotice['password'] }}</strong>
                    </div>
                    <button class="btn btn-primary w-100" type="button" data-close-base-admin>Aceptar, ya guardé la información</button>
                </div>
            </div>
        @endif

        <form class="login-card" action="{{ route('login.post') }}" method="POST" data-login-card @if($recoveryMode) hidden @endif>
            @csrf
            <div class="login-logo">
                <img src="{{ asset('assets/img/LOGO_circulo_SEDES.png') }}" alt="Logo SEDES">
                <img src="{{ asset('assets/img/Logo_gobernacion_cuadrado.png') }}" alt="Gobernación">
            </div>
            <div class="login-title">
                <h1>SEDES</h1>
                <p class="institution-name">SERVICIO DEPARTAMENTAL DE SALUD POTOSÍ</p>
                <p>UNIDAD DE PLANIFICACIÓN Y PROYECTOS</p>
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
                <input class="form-control" id="password" type="password" name="password" placeholder="Ingrese su contraseña" required>
                @error('password')<div class="error-text">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary w-100" type="submit">Ingresar al Sistema</button>
            <button class="login-forgot-link" type="button" data-show-recovery>¿Olvidó su Contraseña?</button>
        </form>

        <div class="login-card" data-recovery-card @if(! $recoveryMode) hidden @endif>
            <div class="login-logo">
                <img src="{{ asset('assets/img/LOGO_circulo_SEDES.png') }}" alt="Logo SEDES">
                <img src="{{ asset('assets/img/Logo_gobernacion_cuadrado.png') }}" alt="Gobernación">
            </div>
            <div class="login-title">
                <h1>SEDES</h1>
                <p>RECUPERACIÓN DE CONTRASEÑA</p>
            </div>

            <p class="recovery-info">Para solicitar la recuperación de su contraseña introduzca su nombre de Usuario y se verificará si usted está registrado.</p>

            @if(session('recovery_error'))
                <div class="alert alert-error">{{ session('recovery_error') }}</div>
            @endif
            @if(session('recovery_message'))
                <div class="alert alert-success">{{ session('recovery_message') }}</div>
            @endif
            @if(session('recovery_success'))
                <div class="alert alert-success">{{ session('recovery_success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.recovery.search') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="recoveryUsername">Usuario</label>
                    <input class="form-control" id="recoveryUsername" name="recovery_username" value="{{ old('recovery_username') }}" placeholder="Ingrese su usuario" required>
                    @error('recovery_username')<div class="error-text">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary w-100" type="submit">Buscar</button>
            </form>

            @if(session('recovery_found'))
                <form method="POST" action="{{ route('password.recovery.token') }}" style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="recovery_username" value="{{ old('recovery_username') }}">
                    <button class="btn btn-gold w-100" type="submit">Solicitar Token</button>
                </form>
            @endif

            <button class="login-forgot-link" type="button" data-show-login>Volver al inicio de sesión</button>
        </div>
    </section>

    <script src="{{ asset('password-toggle.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginCard = document.querySelector('[data-login-card]');
            const recoveryCard = document.querySelector('[data-recovery-card]');

            document.querySelector('[data-show-recovery]')?.addEventListener('click', () => {
                loginCard.hidden = true;
                recoveryCard.hidden = false;
                recoveryCard.querySelector('input[name="recovery_username"]')?.focus();
            });

            document.querySelector('[data-show-login]')?.addEventListener('click', () => {
                recoveryCard.hidden = true;
                loginCard.hidden = false;
                loginCard.querySelector('input[name="username"]')?.focus();
            });

            document.querySelector('[data-close-base-admin]')?.addEventListener('click', (event) => {
                event.target.closest('.base-admin-notice')?.remove();
                loginCard?.querySelector('input[name="username"]')?.focus();
            });

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        });
    </script>
</body>
</html>
