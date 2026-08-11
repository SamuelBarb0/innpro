<x-app-layout>
  <x-slot name="header">Sitio web · Páginas</x-slot>

  <div class="container py-4">
    @include('sitio_admin.partials.avisos')
    @include('sitio_admin.partials.pestanas')

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-1">Páginas del sitio</h5>
        <p class="text-muted small mb-0">
          Cada servicio tiene su propia página y su propia dirección: es lo que le permite competir
          en Google por su búsqueda. Una sola página con todos los servicios dentro no posiciona ninguno.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('sitio.admin.paginas.form') }}">Nueva página</a>
    </div>

    <div class="card shadow">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Título</th>
                <th>Tipo</th>
                <th>Dirección</th>
                <th>SEO</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse($paginas as $p)
                <tr>
                  <td>
                    <strong>{{ $p->titulo }}</strong>
                    @if($p->subtitulo)<div class="text-muted small">{{ $p->subtitulo }}</div>@endif
                  </td>
                  <td><span class="badge bg-secondary">{{ \App\Models\SitioPagina::TIPOS[$p->tipo] ?? $p->tipo }}</span></td>
                  <td><code class="small">{{ str_replace(url('/'), '', $p->url()) ?: '/' }}</code></td>
                  <td>
                    {{-- Sin título o sin descripción propios, Google se inventa
                         el resumen que lee el comprador. Se avisa aquí porque es
                         donde se puede arreglar. --}}
                    @if(blank($p->seo_titulo) || blank($p->seo_descripcion))
                      <span class="badge bg-warning text-dark">Incompleto</span>
                    @elseif($p->seo_noindex)
                      <span class="badge bg-dark">Oculta en Google</span>
                    @else
                      <span class="badge bg-success">Listo</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-{{ $p->activo ? 'success' : 'secondary' }}">
                      {{ $p->activo ? 'Publicada' : 'Borrador' }}
                    </span>
                  </td>
                  <td class="text-center text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('sitio.admin.paginas.form', $p) }}">Editar</a>
                    @if($p->activo)
                      <a class="btn btn-sm btn-outline-secondary" href="{{ $p->url() }}" target="_blank">Ver</a>
                    @endif
                    <form class="d-inline" method="POST" action="{{ route('sitio.admin.paginas.toggle-activo', $p) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-warning">{{ $p->activo ? 'Despublicar' : 'Publicar' }}</button>
                    </form>
                    @if($p->tipo !== \App\Models\SitioPagina::LANDING)
                      <form class="d-inline" method="POST" action="{{ route('sitio.admin.paginas.eliminar', $p) }}"
                            onsubmit="return confirm('¿Eliminar «{{ $p->titulo }}»? Si ya está en Google, mejor despublicarla y dejar una redirección.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No hay páginas todavía.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
