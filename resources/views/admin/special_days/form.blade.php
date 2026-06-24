<div class="form-grid">
    <div class="form-group"><label class="form-label">Nombre de día especial/festivo</label><input class="form-control" name="name" data-auto-format="title" value="{{ old('name', $day?->name) }}" required></div>
    <div class="form-group"><label class="form-label">Fecha</label><input class="form-control" type="date" name="date" value="{{ old('date', $day?->date?->format('Y-m-d')) }}" required></div>
    <div class="form-group"><label class="form-label">Activo</label><select class="form-select" name="active"><option value="1" @selected(old('active', $day?->active ?? 1) == 1)>Sí</option><option value="0" @selected(old('active', $day?->active ?? 1) == 0)>No</option></select></div>
    <div class="form-group span-2"><label class="form-label">Descripción</label><textarea name="description" data-auto-format="sentence">{{ old('description', $day?->description) }}</textarea></div>
</div>
