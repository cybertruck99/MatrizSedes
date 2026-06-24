<div class="form-grid">
    <div class="form-group">
        <label class="form-label">Fecha de inicio</label>
        <input class="form-control" type="date" name="start_date" value="{{ old('start_date', $record?->start_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
        @error('start_date')<div class="error-text">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="form-label">Técnico designado</label>
        <select class="form-select" name="technician_id" required>
            <option value="" @selected(! old('technician_id', $record?->technician_id)) disabled>Seleccione un técnico</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('technician_id', $record?->technician_id) == $user->id)>{{ $user->name }} - {{ $user->cargo ?? 'Cargo no definido' }}</option>
            @endforeach
        </select>
        @error('technician_id')<div class="error-text">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label class="form-label">Estado Inicial</label>
        @php
            $stateValue = $record?->state ?? 'pendiente';
            $stateLabel = ['pendiente'=>'Pendiente','cumplido'=>'Cumplido','no cumplido'=>'No cumplido','retraso'=>'Retraso'][$stateValue] ?? 'Pendiente';
        @endphp
        <div class="readonly-value">{{ $stateLabel }}</div>
    </div>
    <div class="form-group">
        <label class="form-label">Plazo en días hábiles</label>
        <input class="form-control" type="number" min="1" max="365" name="business_days_deadline" value="{{ old('business_days_deadline', $record?->business_days_deadline ?? 1) }}" required>
    </div>
    <div class="form-group span-2">
        <label class="form-label">Tarea asignada</label>
        <textarea name="assigned_task" data-auto-format="sentence" required>{{ old('assigned_task', $record?->assigned_task) }}</textarea>
        @error('assigned_task')<div class="error-text">{{ $message }}</div>@enderror
    </div>
    <div class="form-group span-2">
        <label class="form-label">Observación inicial</label>
        <textarea name="initial_observation" data-auto-format="sentence">{{ old('initial_observation', $record?->initial_observation) }}</textarea>
    </div>
</div>
