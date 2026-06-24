@php
    use App\Models\TaskRecord;
    use App\Models\User;

    $sessionUser = session('auth_user', []);
    $role = $sessionUser['role'] ?? '';
    $currentUser = User::find($sessionUser['id'] ?? session('auth_user_id'));
    $displayName = $currentUser?->name ?? ($sessionUser['name'] ?? 'Usuario');
    $displayCargo = $currentUser?->cargo ?? ($sessionUser['cargo'] ?? 'Cargo no definido');
    $initial = $currentUser?->profile_initial ?? mb_strtoupper(mb_substr($displayName, 0, 1));
    $isHomePage = request()->routeIs('admin.dashboard') || request()->routeIs('user.dashboard') || request()->routeIs('tecnico.dashboard');
    $profileRoute = match ($role) {
        'admin' => route('admin.profile'),
        'tecnico' => route('tecnico.profile'),
        default => route('user.profile'),
    };
    $matrixSubmenuOpen = request()->routeIs('admin.matrix.*') || request()->routeIs('admin.tasks.*');
    $latestMatrixAlert = $role === 'admin'
        ? TaskRecord::whereNotNull('file_review_status')
            ->where('state', 'pendiente')
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->first(['id', 'file_review_status'])
        : null;
    $latestAssignedTaskAlert = $currentUser
        ? TaskRecord::where('technician_id', $currentUser->id)
            ->whereNull('assigned_viewed_at')
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->first(['id'])
        : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Matriz SEDES')</title>
    <link rel="stylesheet" href="{{ asset('css/sedes.css') }}">
</head>
<body>
<div class="app-shell" id="appShell">
    <aside class="sidebar" id="sidebarPanel" aria-hidden="true">
        <button class="sidebar-close-toggle" type="button" data-sidebar-toggle aria-label="Cerrar menú" aria-controls="sidebarPanel">☰</button>
        <div class="brand">
            <img src="{{ asset('assets/img/LOGO_circulo_SEDES.png') }}" alt="SEDES">
            <div>
                <h2>SEDES Potosí</h2>
                <span>Unidad de Planificación y Proyectos</span>
            </div>
        </div>


        <nav class="nav">
            @if($role === 'admin')
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Inicio</a>
                <a class="{{ request()->routeIs('admin.records.*') ? 'active' : '' }}" href="{{ route('admin.records.index') }}">Crear Registro</a>
                <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Gestionar Usuarios</a>
                <a class="{{ request()->routeIs('admin.my-tasks.*') ? 'active' : '' }} {{ $latestAssignedTaskAlert ? 'has-nav-alert' : '' }}" href="{{ route('admin.my-tasks.index') }}">
                    @if($latestAssignedTaskAlert)
                        <span class="nav-notification-dot nav-notification-dot-new" title="Nueva tarea asignada"></span>
                    @endif
                    Ver Mis Tareas
                </a>

                <div class="nav-group {{ $matrixSubmenuOpen ? 'open' : '' }}" data-nav-group>
                    <div class="nav-main {{ $matrixSubmenuOpen ? 'active' : '' }}">
                        <a class="matrix-nav-link" href="{{ route('admin.matrix.index') }}">
                            @if($latestMatrixAlert)
                                <span
                                    class="nav-notification-dot nav-notification-dot-{{ $latestMatrixAlert->file_review_status }}"
                                    title="{{ $latestMatrixAlert->file_review_status === 'new' ? 'Nueva tarea enviada para revisión' : 'Tarea actualizada para revisión' }}"
                                ></span>
                            @endif
                            Ver Matriz de Seguimiento
                        </a>
                        <button type="button" class="nav-arrow" data-submenu-toggle aria-label="Abrir filtros de matriz">▾</button>
                    </div>
                    <div class="nav-submenu">
                        <a href="{{ route('admin.matrix.index', 'cumplidos') }}">✅ Cumplidos</a>
                        <a href="{{ route('admin.matrix.index', 'pendientes') }}">⚪ Pendientes</a>
                        <a href="{{ route('admin.matrix.index', 'no-cumplidos') }}">❌ No Cumplidos</a>
                    </div>
                </div>

                <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Reportes</a>
                <a class="{{ request()->routeIs('admin.special-days.*') ? 'active' : '' }}" href="{{ route('admin.special-days.index') }}">Gestionar Días Especiales</a>
            @elseif($role === 'tecnico')
                <a class="{{ request()->routeIs('tecnico.dashboard') ? 'active' : '' }}" href="{{ route('tecnico.dashboard') }}">Inicio</a>
                <a class="{{ request()->routeIs('tecnico.tasks') || request()->routeIs('tecnico.tasks.show') ? 'active' : '' }} {{ $latestAssignedTaskAlert ? 'has-nav-alert' : '' }}" href="{{ route('tecnico.tasks') }}">
                    @if($latestAssignedTaskAlert)
                        <span class="nav-notification-dot nav-notification-dot-new" title="Nueva tarea asignada"></span>
                    @endif
                    Mis Tareas
                </a>
                <a class="{{ request()->routeIs('tecnico.tasks.upload.index') ? 'active' : '' }}" href="{{ route('tecnico.tasks.upload.index') }}">Subir Tareas</a>
                <a class="{{ request()->routeIs('tecnico.matrix') ? 'active' : '' }}" href="{{ route('tecnico.matrix') }}">Matriz de Seguimiento</a>
                <a class="{{ request()->routeIs('tecnico.users') ? 'active' : '' }}" href="{{ route('tecnico.users') }}">Ver Usuarios</a>
                <a class="{{ request()->routeIs('tecnico.profile') ? 'active' : '' }}" href="{{ route('tecnico.profile') }}">Mi Perfil</a>
            @else
                <a class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}">Página Principal</a>
                <a class="{{ request()->routeIs('user.tasks') || request()->routeIs('user.tasks.show') ? 'active' : '' }} {{ $latestAssignedTaskAlert ? 'has-nav-alert' : '' }}" href="{{ route('user.tasks') }}">
                    @if($latestAssignedTaskAlert)
                        <span class="nav-notification-dot nav-notification-dot-new" title="Nueva tarea asignada"></span>
                    @endif
                    Ver Mis Tareas
                </a>
                <a class="{{ request()->routeIs('user.tasks.upload.index') ? 'active' : '' }}" href="{{ route('user.tasks.upload.index') }}">Subir Tareas</a>
                <a class="{{ request()->routeIs('user.profile') ? 'active' : '' }}" href="{{ route('user.profile') }}">Mi Perfil</a>
            @endif
            <form class="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="logout-btn" type="submit">Cerrar Sesión</button>
            </form>
        </nav>
    </aside>
    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Cerrar menú"></button>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" type="button" id="sidebarToggle" data-sidebar-toggle aria-label="Abrir o cerrar menú" aria-controls="sidebarPanel" aria-expanded="false">☰</button>
                <div class="page-title">
                    <div class="page-heading">
                        <h1>@yield('page_title', 'Sistema de Seguimiento')</h1>
                    </div>
                    <p>@yield('page_subtitle', 'Unidad de Planificación y Proyectos')</p>
                    @hasSection('page_actions')
                        <div class="page-actions">
                            @yield('page_actions')
                        </div>
                    @endif
                </div>
            </div>

            @if($isHomePage)
                <a class="user-pill" href="{{ $profileRoute }}" title="Ir a mi perfil">
                    <div class="avatar">
                        @if($currentUser?->profile_photo_url)
                            <img src="{{ $currentUser->profile_photo_url }}" alt="Fotografía de {{ $displayName }}">
                        @else
                            {{ $initial }}
                        @endif
                    </div>
                    <div class="user-pill-text">
                        <strong>{{ $displayName }}</strong><br>
                        <span class="muted">{{ $displayCargo }}</span>
                    </div>
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">Revise los datos marcados antes de continuar.</div>
        @endif

        @yield('content')
    </main>
</div>

<script src="{{ asset('password-toggle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('appShell');
    const sidebar = document.getElementById('sidebarPanel');
    const backdrop = document.getElementById('sidebarBackdrop');
    const desktopMenu = window.matchMedia('(min-width: 1101px)');

    const isSidebarOpen = () => desktopMenu.matches
        ? !shell?.classList.contains('sidebar-collapsed')
        : Boolean(shell?.classList.contains('sidebar-open'));

    const setSidebarOpen = (open) => {
        shell?.classList.toggle('sidebar-collapsed', desktopMenu.matches && !open);
        shell?.classList.toggle('sidebar-open', !desktopMenu.matches && open);
        document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        sidebar?.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.body.classList.toggle('menu-open', !desktopMenu.matches && open);
    };

    const syncSidebarMode = () => {
        shell?.classList.remove('sidebar-open', 'sidebar-collapsed');
        setSidebarOpen(desktopMenu.matches);
    };

    const initInterface = () => {
        window.initPasswordToggles?.();

        document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
            if (!button.dataset.bound) {
                button.dataset.bound = '1';
                button.addEventListener('click', () => setSidebarOpen(!isSidebarOpen()));
            }
            button.setAttribute('aria-expanded', isSidebarOpen() ? 'true' : 'false');
        });

        if (shell && !document.body.dataset.sidebarBound) {
            document.body.dataset.sidebarBound = '1';
            backdrop?.addEventListener('click', () => setSidebarOpen(false));
            desktopMenu.addEventListener('change', syncSidebarMode);
            syncSidebarMode();

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setSidebarOpen(false);
                }
            });

            sidebar?.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (!desktopMenu.matches) {
                        setSidebarOpen(false);
                    }
                });
            });
        }

        document.querySelectorAll('[data-submenu-toggle]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                button.closest('[data-nav-group]')?.classList.toggle('open');
            });
        });

        const uppercaseFirstLetter = (value) => value.replace(
            /\p{L}/u,
            (letter) => letter.toLocaleUpperCase('es-BO')
        );

        const titleCase = (value) => value.replace(
            /(^|[\s\-\/])(\p{L})/gu,
            (match, prefix, letter) => prefix + letter.toLocaleUpperCase('es-BO')
        );

        const sentenceCase = (value) => {
            const parts = value.split(/(\p{L}[\p{L}\p{M}]*)/gu);
            let startsSentence = true;

            return parts.map((part, index) => {
                if (index % 2 === 1) {
                    const letterCount = Array.from(part.matchAll(/\p{L}/gu)).length;
                    const formatted = (startsSentence || letterCount >= 3)
                        ? uppercaseFirstLetter(part)
                        : part;
                    startsSentence = false;
                    return formatted;
                }

                if (/[.\r\n]/u.test(part)) {
                    startsSentence = true;
                }

                return part;
            }).join('');
        };

        const paragraphSentenceCase = (value) => value.replace(
            /(^|[.!?]\s+|\n+\s*)(\p{L})/gu,
            (match, prefix, letter) => prefix + letter.toLocaleUpperCase('es-BO')
        );

        document.querySelectorAll('[data-auto-format]').forEach((field) => {
            if (field.dataset.formatBound) return;
            field.dataset.formatBound = '1';

            const applyFormat = (force = false) => {
                const mode = field.dataset.autoFormat;
                const value = field.value;
                if (!value) return;

                if (!force && !/[\s.!?]$/u.test(value)) return;

                const start = field.selectionStart;
                const end = field.selectionEnd;
                const formatted = mode === 'paragraph'
                    ? paragraphSentenceCase(value)
                    : (mode === 'sentence' ? sentenceCase(value) : titleCase(value));

                if (formatted !== value) {
                    field.value = formatted;
                    if (typeof start === 'number' && typeof end === 'number') {
                        field.setSelectionRange(Math.min(start, formatted.length), Math.min(end, formatted.length));
                    }
                }
            };

            field.addEventListener('input', () => applyFormat(false));
            field.addEventListener('blur', () => applyFormat(true));
        });

        document.querySelectorAll('[data-area-select]').forEach((select) => {
            if (select.dataset.areaBound) return;
            select.dataset.areaBound = '1';

            const wrapper = select.closest('.form-group');
            const otherInput = wrapper?.querySelector('[data-area-other-input]');
            const syncAreaInput = () => {
                const useOther = select.value === 'OTRA AREA';
                if (!otherInput) return;

                otherInput.hidden = !useOther;
                otherInput.required = useOther;
                if (!useOther) {
                    otherInput.value = '';
                }
            };

            select.addEventListener('change', syncAreaInput);
            syncAreaInput();
        });

        document.querySelectorAll('[data-force-uppercase]').forEach((field) => {
            if (field.dataset.uppercaseBound) return;
            field.dataset.uppercaseBound = '1';

            const forceUppercase = () => {
                const start = field.selectionStart;
                const end = field.selectionEnd;
                const upper = field.value.toLocaleUpperCase('es-BO');

                if (field.value !== upper) {
                    field.value = upper;
                    if (typeof start === 'number' && typeof end === 'number') {
                        field.setSelectionRange(start, end);
                    }
                }
            };

            field.addEventListener('input', forceUppercase);
            field.addEventListener('blur', forceUppercase);
        });

        document.querySelectorAll('.report-menu a[target="_blank"]').forEach((link) => {
            if (link.dataset.reportMenuCloseBound) return;
            link.dataset.reportMenuCloseBound = '1';
            link.addEventListener('click', () => {
                window.setTimeout(() => {
                    const details = link.closest('details');
                    if (details) details.open = false;
                }, 120);
            });
        });

        const rotateReportRequestKey = (form) => {
            const key = form.querySelector('[data-report-request-key]');
            if (!key) return;

            key.value = crypto?.randomUUID
                ? crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
        };

        const temporarilyDisableSubmitters = (form) => {
            const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            buttons.forEach((button) => button.disabled = true);
            window.setTimeout(() => buttons.forEach((button) => button.disabled = false), 1600);
            window.setTimeout(() => rotateReportRequestKey(form), 1600);
        };

        document.querySelectorAll('.report-menu form[target="_blank"]').forEach((form) => {
            if (form.dataset.reportMenuSubmitBound) return;
            form.dataset.reportMenuSubmitBound = '1';
            form.addEventListener('submit', () => {
                temporarilyDisableSubmitters(form);
                window.setTimeout(() => {
                    const details = form.closest('details');
                    if (details) details.open = false;
                }, 120);
            });
        });

        document.querySelectorAll('[data-countdown]').forEach((element) => {
            if (element.dataset.countdownBound) return;
            element.dataset.countdownBound = '1';

            const due = element.dataset.due ? new Date(element.dataset.due) : null;
            const reviewState = 'pendiente';
            const reviewLabel = 'Pendiente';
            const deliveryState = element.dataset.deliveryState || 'not-submitted';
            const submittedAt = element.dataset.submittedAt ? new Date(element.dataset.submittedAt) : null;

            if (reviewState !== 'pendiente') {
                element.textContent = `Revisión finalizada: ${reviewLabel}.`;
                element.classList.add(`status-${reviewState.replace(' ', '-')}`);
                return;
            }

            if (!due || Number.isNaN(due.getTime())) {
                element.textContent = 'Sin fecha de vencimiento';
                return;
            }

            if (false && deliveryState === 'submitted') {
                const submittedLate = submittedAt
                    && !Number.isNaN(submittedAt.getTime())
                    && submittedAt.getTime() > due.getTime();

                element.textContent = submittedLate
                    ? 'Enviado para revisión fuera del plazo.'
                    : 'Enviado para revisión dentro del plazo.';
                element.classList.toggle('overdue', Boolean(submittedLate));
                element.classList.toggle('submitted-on-time', !submittedLate);
                return;
            }

            const plural = (value, singular, pluralText) => `${value} ${value === 1 ? singular : pluralText}`;
            const formatRemaining = (diffMs) => {
                const totalMinutes = Math.max(0, Math.floor(diffMs / 60000));
                const days = Math.floor(totalMinutes / 1440);
                const hours = Math.floor((totalMinutes % 1440) / 60);
                const minutes = totalMinutes % 60;

                if (days > 0) {
                    return `${plural(days, 'Día', 'Días')} ${plural(hours, 'hora', 'horas')} restante`;
                }
                if (hours > 0) {
                    return `${plural(hours, 'hora', 'horas')} ${plural(minutes, 'minuto', 'minutos')} restante`;
                }
                return `${plural(minutes, 'minuto', 'minutos')} restante`;
            };

            const formatOverdue = (diffMs) => {
                const totalMinutes = Math.max(1, Math.floor(Math.abs(diffMs) / 60000));
                const days = Math.floor(totalMinutes / 1440);
                const hours = Math.floor((totalMinutes % 1440) / 60);
                const minutes = totalMinutes % 60;

                if (days > 0) {
                    return `La Tarea esta retrasada por: ${plural(days, 'día', 'días')} ${plural(hours, 'hora', 'horas')}`;
                }
                if (hours > 0) {
                    return `La Tarea esta retrasada por: ${plural(hours, 'hora', 'horas')} ${plural(minutes, 'minuto', 'minutos')}`;
                }
                return `La Tarea esta retrasada por: ${plural(minutes, 'minuto', 'minutos')}`;
            };

            const formatDuration = (diffMs, sentTask = false) => {
                const totalSeconds = Math.max(0, Math.floor(Math.abs(diffMs) / 1000));
                const minute = 60;
                const hour = minute * 60;
                const day = hour * 24;
                const month = day * 30;

                if (sentTask && totalSeconds > month * 12) {
                    return null;
                }

                if (totalSeconds < minute) {
                    return plural(Math.max(1, totalSeconds), 'segundo', 'segundos');
                }

                if (totalSeconds < hour) {
                    return plural(Math.floor(totalSeconds / minute), 'minuto', 'minutos');
                }

                if (totalSeconds >= month) {
                    const months = Math.floor(totalSeconds / month);
                    const days = Math.floor((totalSeconds % month) / day);
                    return days > 0
                        ? `${plural(months, 'mes', 'meses')} ${plural(days, 'dia', 'dias')}`
                        : plural(months, 'mes', 'meses');
                }

                const days = Math.floor(totalSeconds / day);
                const hours = Math.floor((totalSeconds % day) / hour);

                if (days > 0) {
                    return hours > 0
                        ? `${plural(days, 'dia', 'dias')} ${plural(hours, 'hora', 'horas')}`
                        : plural(days, 'dia', 'dias');
                }

                return plural(Math.floor(totalSeconds / hour), 'hora', 'horas');
            };

            const setTimeState = (state) => {
                element.classList.toggle('pending-time', state === 'pending');
                element.classList.toggle('submitted-on-time', state === 'sent-on-time');
                element.classList.toggle('overdue', state === 'overdue' || state === 'sent-late');
            };

            const tick = () => {
                if (
                    deliveryState === 'submitted'
                    && submittedAt
                    && !Number.isNaN(submittedAt.getTime())
                ) {
                    const diff = due.getTime() - submittedAt.getTime();
                    const duration = formatDuration(diff, true);

                    if (!duration) {
                        element.textContent = 'la tarea fue enviada hace tiempo';
                        setTimeState(diff >= 0 ? 'sent-on-time' : 'sent-late');
                        return;
                    }

                    element.textContent = diff >= 0
                        ? `La Tarea Fue enviada: ${duration} antes`
                        : `La Tarea fue enviada: ${duration} despues`;
                    setTimeState(diff >= 0 ? 'sent-on-time' : 'sent-late');
                    return;
                }

                if (deliveryState === 'submitted') {
                    element.textContent = 'la tarea fue enviada hace tiempo';
                    setTimeState('sent-on-time');
                    return;
                }

                const diff = due.getTime() - Date.now();
                const duration = formatDuration(diff);

                element.textContent = diff >= 0
                    ? `${duration} restante`
                    : `La Tarea esta retrasada por: ${duration}`;
                setTimeState(diff >= 0 ? 'pending' : 'overdue');
            };

            tick();
            setInterval(tick, 1000);
        });

        const bindPreparedUpload = (form) => {
            const limit = Number(form.dataset.fileLimit || 30);
            const maxTotalSize = Number(form.dataset.maxTotalSize || 52428800);
            const prepareUrl = form.dataset.prepareUrl;
            const clearUrl = form.dataset.clearUrl;
            const cameraButton = form.querySelector('[data-camera-button]');
            const cameraInput = form.querySelector('[data-camera-input]');
            const sourceInputs = Array.from(form.querySelectorAll('[data-file-source]'));
            const preparedContainer = form.querySelector('[data-prepared-files]');
            const list = form.querySelector('[data-selected-files]');
            const dropZone = form.querySelector('[data-file-drop-zone]');
            const progress = form.querySelector('[data-upload-progress]');
            const progressBar = form.querySelector('[data-upload-bar]');
            const progressPercent = form.querySelector('[data-upload-percent]');
            const progressMessage = form.querySelector('[data-upload-message]');
            const submitButton = form.querySelector('button[type="submit"]');
            const clearButton = form.querySelector('[data-clear-files]');
            const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
            const allowedExtensions = new Set([
                'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'xlsb', 'ods',
                'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'odp', 'jpg', 'jpeg', 'png', 'webp',
                'gif', 'bmp', 'heic', 'heif', 'mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v',
                '3gp', 'zip', 'rar',
            ]);
            const preparedFiles = Array.from(form.querySelectorAll('[data-prepared-token]')).map((input) => ({
                token: input.value,
                name: input.dataset.fileName || 'archivo',
                size: Number(input.dataset.fileSize || 0),
            }));
            const uploadQueue = [];
            let activeRequest = null;
            let activeFile = null;
            let clearingFiles = false;

            const escapeHtml = (text) => String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const renderPreparedFiles = () => {
                if (!list) return;

                if (preparedFiles.length === 0) {
                    list.textContent = activeRequest || uploadQueue.length
                        ? 'Preparando archivos. Los nombres aparecerán al completar la carga.'
                        : 'Aún no hay archivos preparados.';
                    submitButton.disabled = true;
                    return;
                }

                list.innerHTML = `<ul class="prepared-file-list">${preparedFiles
                    .map((file) => `<li><span>${escapeHtml(file.name)}</span></li>`)
                    .join('')}</ul>`;
                submitButton.disabled = Boolean(activeRequest || uploadQueue.length);
            };

            const showProgress = (percentage, message) => {
                if (!progress || !progressBar || !progressPercent || !progressMessage) return;
                progress.hidden = false;
                progressBar.style.width = `${percentage}%`;
                progressPercent.textContent = `${percentage}%`;
                progressMessage.textContent = message;
            };

            const responseError = (request) => {
                let message = 'No se pudo preparar el archivo. Intente nuevamente.';
                try {
                    const response = JSON.parse(request.responseText);
                    const firstError = Object.values(response.errors || {}).flat()[0];
                    message = firstError || response.message || message;
                } catch (error) {
                    // Se conserva el mensaje general si la respuesta no es JSON.
                }
                return message;
            };

            const prepareNextFile = () => {
                if (activeRequest || uploadQueue.length === 0) {
                    renderPreparedFiles();
                    return;
                }

                const file = uploadQueue.shift();
                const request = new XMLHttpRequest();
                const payload = new FormData();
                payload.append('_token', csrfToken);
                payload.append('archivo', file, file.name);
                activeRequest = request;
                activeFile = file;
                showProgress(0, 'Preparando archivo...');
                renderPreparedFiles();

                request.open('POST', prepareUrl, true);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.setRequestHeader('Accept', 'application/json');

                request.upload.addEventListener('progress', (uploadEvent) => {
                    if (!uploadEvent.lengthComputable) return;
                    const percentage = Math.min(99, Math.round((uploadEvent.loaded / uploadEvent.total) * 100));
                    showProgress(percentage, 'Preparando archivo...');
                });

                request.addEventListener('load', () => {
                    activeRequest = null;
                    activeFile = null;

                    if (request.status >= 200 && request.status < 300) {
                        const response = JSON.parse(request.responseText);
                        preparedFiles.push(response);
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'prepared_files[]';
                        hidden.value = response.token;
                        hidden.dataset.preparedToken = '1';
                        hidden.dataset.fileName = response.name;
                        hidden.dataset.fileSize = String(response.size || 0);
                        preparedContainer.appendChild(hidden);
                        showProgress(100, uploadQueue.length ? 'Archivo listo. Preparando el siguiente...' : 'Archivo listo para subir.');
                    } else if (!clearingFiles) {
                        showProgress(0, 'La preparación no se completó.');
                        alert(responseError(request));
                    }

                    renderPreparedFiles();
                    prepareNextFile();
                });

                request.addEventListener('error', () => {
                    activeRequest = null;
                    activeFile = null;
                    if (!clearingFiles) {
                        showProgress(0, 'Se perdió la conexión durante la carga.');
                        alert('No se pudo preparar el archivo. Revise su conexión e intente nuevamente.');
                    }
                    renderPreparedFiles();
                    prepareNextFile();
                });

                request.addEventListener('abort', () => {
                    activeRequest = null;
                    activeFile = null;
                    renderPreparedFiles();
                });

                request.send(payload);
            };

            const addFiles = (incoming) => {
                for (const file of incoming) {
                    const pendingTotal = preparedFiles.length + uploadQueue.length + (activeRequest ? 1 : 0);
                    if (pendingTotal >= limit) {
                        alert(`Solo puede seleccionar hasta ${limit} archivos.`);
                        break;
                    }
                    const extension = file.name.includes('.')
                        ? file.name.split('.').pop().toLocaleLowerCase('es-BO')
                        : '';
                    if (!allowedExtensions.has(extension)) {
                        alert(`El archivo ${file.name} tiene un formato no permitido.`);
                        continue;
                    }
                    const preparedSize = preparedFiles.reduce((total, prepared) => total + prepared.size, 0);
                    const queuedSize = uploadQueue.reduce((total, queued) => total + queued.size, 0);
                    const currentTotalSize = preparedSize + queuedSize + (activeFile?.size || 0);
                    if ((currentTotalSize + file.size) > maxTotalSize) {
                        alert('La suma total de los archivos seleccionados no puede superar los 50 MB.');
                        continue;
                    }
                    uploadQueue.push(file);
                }

                renderPreparedFiles();
                prepareNextFile();
            };

            const clearServerDrafts = () => {
                window.setTimeout(() => {
                    fetch(clearUrl, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    }).finally(() => {
                        clearingFiles = false;
                    });
                }, 250);
            };

            clearButton?.addEventListener('click', () => {
                clearingFiles = true;
                uploadQueue.splice(0);
                activeRequest?.abort();
                preparedFiles.splice(0);
                preparedContainer.innerHTML = '';
                sourceInputs.forEach((input) => {
                    input.value = '';
                });
                if (progress) progress.hidden = true;
                if (progressBar) progressBar.style.width = '0%';
                if (progressPercent) progressPercent.textContent = '0%';
                renderPreparedFiles();
                clearServerDrafts();
            });

            cameraButton?.addEventListener('click', () => cameraInput?.click());
            sourceInputs.forEach((input) => input.addEventListener('change', () => {
                addFiles(Array.from(input.files || []));
                input.value = '';
            }));

            if (dropZone) {
                let dragDepth = 0;

                dropZone.addEventListener('dragenter', (event) => {
                    event.preventDefault();
                    dragDepth++;
                    dropZone.classList.add('is-dragging');
                });

                dropZone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'copy';
                });

                dropZone.addEventListener('dragleave', (event) => {
                    event.preventDefault();
                    dragDepth = Math.max(0, dragDepth - 1);
                    if (dragDepth === 0) {
                        dropZone.classList.remove('is-dragging');
                    }
                });

                dropZone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    dragDepth = 0;
                    dropZone.classList.remove('is-dragging');
                    addFiles(Array.from(event.dataTransfer.files || []));
                });
            }

            form.addEventListener('submit', (event) => {
                if (activeRequest || uploadQueue.length) {
                    event.preventDefault();
                    alert('Espere a que todos los archivos terminen de prepararse.');
                    return;
                }
                if (preparedFiles.length === 0) {
                    event.preventDefault();
                    alert('Debe seleccionar al menos un archivo.');
                    return;
                }

                submitButton.disabled = true;
                showProgress(100, 'Confirmando archivos en la tarea...');
            });

            renderPreparedFiles();
        };

        document.querySelectorAll('form[data-file-limit]').forEach((form) => {
            if (form.dataset.filesBound) return;
            form.dataset.filesBound = '1';

            if (form.dataset.prepareUrl) {
                bindPreparedUpload(form);
                return;
            }

            const limit = Number(form.dataset.fileLimit || 30);
            const maxSize = Number(form.dataset.maxFileSize || 52428800);
            const cameraButton = form.querySelector('[data-camera-button]');
            const cameraInput = form.querySelector('[data-camera-input]');
            const sourceInputs = Array.from(form.querySelectorAll('[data-file-source]'));
            const finalInput = form.querySelector('[data-final-files]');
            const list = form.querySelector('[data-selected-files]');
            const dropZone = form.querySelector('[data-file-drop-zone]');
            const progress = form.querySelector('[data-upload-progress]');
            const progressBar = form.querySelector('[data-upload-bar]');
            const progressPercent = form.querySelector('[data-upload-percent]');
            const progressMessage = form.querySelector('[data-upload-message]');
            const submitButton = form.querySelector('button[type="submit"]');
            const clearButton = form.querySelector('[data-clear-files]');
            const allowedExtensions = new Set([
                'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'xlsb', 'ods',
                'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'odp', 'jpg', 'jpeg', 'png', 'webp',
                'zip', 'rar',
            ]);
            const transfer = window.DataTransfer ? new DataTransfer() : null;
            if (!transfer) {
                sourceInputs.forEach((input) => input.setAttribute('name', 'archivos[]'));
            }

            const escapeHtml = (text) => String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const files = () => transfer ? Array.from(transfer.files) : sourceInputs.flatMap((input) => Array.from(input.files || []));
            const syncFinalInput = () => {
                if (transfer && finalInput) {
                    finalInput.files = transfer.files;
                }
            };
            const render = () => {
                const selected = files();
                if (!list) return;

                if (selected.length === 0) {
                    list.textContent = 'Sin archivos seleccionados.';
                    return;
                }

                const totalSize = selected.reduce((total, file) => total + file.size, 0);
                list.textContent = `${selected.length} ${selected.length === 1 ? 'archivo seleccionado' : 'archivos seleccionados'} · ${(totalSize / 1048576).toFixed(2)} MB`;
            };

            const addFiles = (incoming) => {
                if (!transfer) {
                    render();
                    return;
                }

                for (const file of incoming) {
                    if (transfer.items.length >= limit) {
                        alert(`Solo puede seleccionar hasta ${limit} archivos.`);
                        break;
                    }
                    const extension = file.name.includes('.')
                        ? file.name.split('.').pop().toLocaleLowerCase('es-BO')
                        : '';
                    if (!allowedExtensions.has(extension)) {
                        alert(`El archivo ${file.name} tiene un formato no permitido.`);
                        continue;
                    }
                    if (file.size > maxSize) {
                        alert(`El archivo ${file.name} supera el tamaño máximo de 50 MB.`);
                        continue;
                    }
                    transfer.items.add(file);
                }

                syncFinalInput();
                render();
            };

            clearButton?.addEventListener('click', () => {
                if (transfer) {
                    transfer.items.clear();
                    syncFinalInput();
                }
                sourceInputs.forEach((input) => {
                    input.value = '';
                });
                if (finalInput && !transfer) {
                    finalInput.value = '';
                }
                if (progress) {
                    progress.hidden = true;
                }
                if (progressBar) {
                    progressBar.style.width = '0%';
                }
                if (progressPercent) {
                    progressPercent.textContent = '0%';
                }
                render();
            });

            cameraButton?.addEventListener('click', () => cameraInput?.click());
            sourceInputs.forEach((input) => input.addEventListener('change', () => {
                addFiles(Array.from(input.files || []));
                input.value = '';
            }));

            if (dropZone) {
                let dragDepth = 0;

                dropZone.addEventListener('dragenter', (event) => {
                    event.preventDefault();
                    dragDepth++;
                    dropZone.classList.add('is-dragging');
                });

                dropZone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'copy';
                });

                dropZone.addEventListener('dragleave', (event) => {
                    event.preventDefault();
                    dragDepth = Math.max(0, dragDepth - 1);
                    if (dragDepth === 0) {
                        dropZone.classList.remove('is-dragging');
                    }
                });

                dropZone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    dragDepth = 0;
                    dropZone.classList.remove('is-dragging');
                    addFiles(Array.from(event.dataTransfer.files || []));
                });
            }

            form.addEventListener('submit', (event) => {
                syncFinalInput();
                const selected = files();
                if (selected.length === 0) {
                    event.preventDefault();
                    alert('Debe seleccionar al menos un archivo.');
                    return;
                }
                if (selected.length > limit) {
                    event.preventDefault();
                    alert(`Solo puede seleccionar hasta ${limit} archivos.`);
                    return;
                }
                const oversized = selected.find((file) => file.size > maxSize);
                if (oversized) {
                    event.preventDefault();
                    alert(`El archivo ${oversized.name} supera el tamaño máximo de 50 MB.`);
                    return;
                }

                if (!form.hasAttribute('data-ajax-upload') || !transfer || !window.XMLHttpRequest) {
                    return;
                }

                event.preventDefault();
                const formData = new FormData(form);
                const request = new XMLHttpRequest();

                progress.hidden = false;
                progressBar.style.width = '0%';
                progressPercent.textContent = '0%';
                progressMessage.textContent = selected.length === 1 ? 'Subiendo archivo...' : 'Subiendo archivos...';
                list.textContent = 'Carga en proceso. El nombre aparecerá cuando finalice correctamente.';
                submitButton.disabled = true;
                sourceInputs.forEach((input) => input.disabled = true);

                request.open(form.method || 'POST', form.action, true);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.setRequestHeader('Accept', 'application/json');

                request.upload.addEventListener('progress', (uploadEvent) => {
                    if (!uploadEvent.lengthComputable) return;
                    const percentage = Math.min(99, Math.round((uploadEvent.loaded / uploadEvent.total) * 100));
                    progressBar.style.width = `${percentage}%`;
                    progressPercent.textContent = `${percentage}%`;
                });

                request.addEventListener('load', () => {
                    if (request.status >= 200 && request.status < 400) {
                        progressBar.style.width = '100%';
                        progressPercent.textContent = '100%';
                        progressMessage.textContent = 'Archivo subido correctamente.';
                        window.setTimeout(() => window.location.reload(), 450);
                        return;
                    }

                    let message = 'No se pudo subir el archivo. Intente nuevamente.';
                    if (request.status === 422) {
                        try {
                            const response = JSON.parse(request.responseText);
                            const firstError = Object.values(response.errors || {}).flat()[0];
                            message = firstError || response.message || message;
                        } catch (error) {
                            // Se conserva el mensaje general si la respuesta no es JSON.
                        }
                    }

                    progressMessage.textContent = 'La carga no se completó.';
                    alert(message);
                    submitButton.disabled = false;
                    sourceInputs.forEach((input) => input.disabled = false);
                });

                request.addEventListener('error', () => {
                    progressMessage.textContent = 'Se perdió la conexión durante la carga.';
                    alert('No se pudo completar la carga. Revise su conexión e intente nuevamente.');
                    submitButton.disabled = false;
                    sourceInputs.forEach((input) => input.disabled = false);
                });

                request.send(formData);
            });
        });

        document.querySelectorAll('[data-row-href]').forEach((row) => {
            if (row.dataset.rowLinkBound) return;
            row.dataset.rowLinkBound = '1';

            const openTask = (event) => {
                if (event.target.closest('a, button, input, select, textarea, label, form')) {
                    return;
                }
                window.location.href = row.dataset.rowHref;
            };

            row.addEventListener('click', openTask);
            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                openTask(event);
            });
        });

        document.querySelectorAll('[data-confirm-modal]').forEach((modal) => {
            if (modal.dataset.confirmBound) return;
            modal.dataset.confirmBound = '1';

            const openModal = () => {
                modal.hidden = false;
                document.body.classList.add('confirmation-open');
                window.setTimeout(() => modal.querySelector('[data-confirm-password]')?.focus(), 50);
            };
            const closeModal = () => {
                modal.hidden = true;
                document.body.classList.remove('confirmation-open');
                const password = modal.querySelector('[data-confirm-password]');
                if (password) {
                    password.value = '';
                    window.setPasswordVisibility?.(password, false);
                }
            };

            document.querySelectorAll(`[data-confirm-open="${modal.id}"]`).forEach((button) => {
                button.addEventListener('click', openModal);
            });
            modal.querySelectorAll('[data-confirm-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });
            modal.querySelectorAll('form[data-close-on-submit]').forEach((form) => {
                form.addEventListener('submit', () => {
                    temporarilyDisableSubmitters(form);
                    window.setTimeout(closeModal, 120);
                });
            });
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            modal.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeModal();
            });

            if (modal.dataset.autoOpen === '1') {
                openModal();
            }
        });

        document.querySelectorAll('[data-online-users]').forEach((counter) => {
            if (counter.dataset.onlineBound) return;
            counter.dataset.onlineBound = '1';

            const refreshOnlineUsers = async () => {
                try {
                    const response = await fetch(counter.dataset.onlineUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        cache: 'no-store',
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    counter.textContent = data.count;
                } catch (error) {
                    // Se mantiene el último valor disponible si se pierde la conexión.
                }
            };

            window.setInterval(refreshOnlineUsers, 15000);
        });

        document.querySelectorAll('form[data-live-search]').forEach((form) => {
            if (form.dataset.liveBound) return;
            form.dataset.liveBound = '1';

            let timer = null;
            let controller = null;

            const liveSubmit = (focusedName = 'search', cursorPosition = null) => {
                clearTimeout(timer);
                timer = setTimeout(async () => {
                    const formData = new FormData(form);
                    const params = new URLSearchParams();
                    for (const [key, value] of formData.entries()) {
                        if (value !== null && String(value).trim() !== '') {
                            params.append(key, value);
                        }
                    }

                    const baseUrl = form.getAttribute('action') || window.location.pathname;
                    const url = `${baseUrl}${params.toString() ? '?' + params.toString() : ''}`;
                    if (controller) controller.abort();
                    controller = new AbortController();

                    try {
                        const shouldRefocus = form.dataset.keepSearchFocus === '1';
                        const response = await fetch(url, {
                            headers: {'X-Requested-With': 'XMLHttpRequest'},
                            signal: controller.signal,
                        });
                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newMain = doc.querySelector('.main');
                        const currentMain = document.querySelector('.main');
                        if (!newMain || !currentMain) return;

                        currentMain.innerHTML = newMain.innerHTML;
                        window.history.replaceState({}, '', url);
                        initInterface();

                        const refocused = document.querySelector(`form[data-live-search] [name="${focusedName}"]`);
                        if (shouldRefocus && refocused) {
                            refocused.focus({preventScroll:true});
                            if (typeof cursorPosition === 'number' && 'setSelectionRange' in refocused) {
                                refocused.setSelectionRange(cursorPosition, cursorPosition);
                            }
                        }
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            form.submit();
                        }
                    }
                }, 320);
            };

            form.querySelectorAll('input[type="search"], input[name="search"]').forEach((input) => {
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        clearTimeout(timer);
                        form.dataset.keepSearchFocus = '0';
                        input.blur();
                    }
                });
                input.addEventListener('blur', () => {
                    if (document.activeElement !== input) {
                        form.dataset.keepSearchFocus = '0';
                    }
                });
                input.addEventListener('input', () => {
                    form.dataset.keepSearchFocus = '1';
                    liveSubmit(input.name, input.selectionStart ?? input.value.length);
                });
            });

            form.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => liveSubmit(select.name));
            });
        });
    };

    initInterface();
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>
</body>
</html>
