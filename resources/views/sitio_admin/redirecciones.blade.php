<x-app-layout>
  <x-slot name="header">Sitio web · Redirecciones</x-slot>

  <div class="container py-4">
    @include('sitio_admin.partials.avisos')

    <div class="alert alert-info">
      Cuando el dominio pase a este sitio, las direcciones del sitio anterior que Google ya tiene guardadas
      dejarán de existir. Cada una que quede sin redirigir es una visita perdida — y muchas juntas hacen que
      Google trate el dominio como si fuera nuevo, con lo que se pierde la antigüedad, que es lo único que no
      se puede recuperar trabajando.
      <br><br>
      <strong>Cómo se van encontrando:</strong> en Search Console, en el informe de páginas con error 404.
      Van apareciendo durante semanas, no todas el primer día; por eso se agregan aquí y no en el código.
    </div>

    <div class="card shadow mb-4">
      <div class="card-body">
        <h5 class="mb-3">Agregar redirección</h5>
        <form method="POST" action="{{ route('sitio.admin.redirecciones.guardar') }}" class="row g-3">
          @csrf
          <div class="col-md-4">
            <label class="form-label" for="origen">Dirección vieja</label>
            <input type="text" class="form-control" id="origen" name="origen" required
                   placeholder="servicios/camaras">
            <div class="form-text">Puedes pegarla completa con dominio; se recorta sola.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="destino">Dirección nueva</label>
            <input type="text" class="form-control" id="destino" name="destino" required
                   placeholder="/servicios/camaras-de-seguridad-cctv-bogota">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="codigo">Tipo</label>
            <select class="form-select" id="codigo" name="codigo">
              <option value="301">301 · definitiva</option>
              <option value="302">302 · temporal</option>
            </select>
            <div class="form-text">La 301 traspasa el posicionamiento.</div>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Agregar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow">
      <div class="card-body">
        <h5 class="mb-3">Redirecciones activas</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Vieja</th>
                <th>Nueva</th>
                <th>Tipo</th>
                <th class="text-end">Usos</th>
                <th>Último uso</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($redirecciones as $r)
                <tr class="{{ $r->activo ? '' : 'opacity-50' }}">
                  <td><code class="small">/{{ $r->origen }}</code></td>
                  <td><code class="small">{{ $r->destino }}</code></td>
                  <td><span class="badge bg-{{ $r->codigo === 301 ? 'success' : 'warning text-dark' }}">{{ $r->codigo }}</span></td>
                  {{-- Los usos dicen qué direcciones viejas siguen vivas en Google:
                       una con muchos golpes todavía trae gente; una en cero lleva
                       tiempo sin que nadie la pida. --}}
                  <td class="text-end">{{ $r->golpes }}</td>
                  <td class="small text-muted">{{ $r->ultimo_golpe_at?->format('d/m/Y H:i') ?? '—' }}</td>
                  <td class="text-center">
                    <form class="d-inline" method="POST" action="{{ route('sitio.admin.redirecciones.eliminar', $r) }}"
                          onsubmit="return confirm('¿Eliminar la redirección de /{{ $r->origen }}?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay redirecciones.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
