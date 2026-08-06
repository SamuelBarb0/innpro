<x-app-layout>
  <x-slot name="header">Sitio web · {{ $pagina->exists ? 'Editar página' : 'Nueva página' }}</x-slot>

  <div class="container py-4">
    @include('sitio_admin.partials.avisos')

    <form method="POST" action="{{ route('sitio.admin.paginas.guardar') }}">
      @csrf
      <input type="hidden" name="id" value="{{ $pagina->id }}">

      <div class="card shadow mb-4">
        <div class="card-body">
          <h5 class="mb-3">Contenido</h5>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="tipo">Tipo</label>
              <select class="form-select" id="tipo" name="tipo" {{ $pagina->tipo === 'landing' ? 'disabled' : '' }}>
                @foreach(\App\Models\SitioPagina::TIPOS as $valor => $etiqueta)
                  <option value="{{ $valor }}" @selected(old('tipo', $pagina->tipo) === $valor)>{{ $etiqueta }}</option>
                @endforeach
              </select>
              @if($pagina->tipo === 'landing')
                {{-- La portada vive en la raíz: cambiarle el tipo la dejaría sin URL. --}}
                <input type="hidden" name="tipo" value="landing">
                <div class="form-text">La portada no cambia de tipo.</div>
              @endif
            </div>

            <div class="col-md-8">
              <label class="form-label" for="titulo">Título <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="titulo" name="titulo" required
                     value="{{ old('titulo', $pagina->titulo) }}">
              <div class="form-text">Es el encabezado grande de la página (el H1).</div>
            </div>

            <div class="col-md-12">
              <label class="form-label" for="slug">Dirección de la página <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">{{ url('/') }}/servicios/</span>
                <input type="text" class="form-control" id="slug" name="slug" required
                       value="{{ old('slug', $pagina->slug) }}" placeholder="camaras-de-seguridad-cctv-bogota">
              </div>
              <div class="form-text">
                Solo minúsculas, números y guiones. <strong>Escribe aquí lo que la gente busca</strong>
                (el servicio y la ciudad): esta dirección es de lo primero que lee Google.
                Si la página ya está publicada y posicionada, cambiarla hace perder ese avance —
                si toca cambiarla, deja una redirección de la vieja a la nueva.
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label" for="subtitulo">Subtítulo</label>
              <input type="text" class="form-control" id="subtitulo" name="subtitulo"
                     value="{{ old('subtitulo', $pagina->subtitulo) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label" for="orden">Orden</label>
              <input type="number" class="form-control" id="orden" name="orden" min="0"
                     value="{{ old('orden', $pagina->orden ?? 0) }}">
              <div class="form-text">Menor = aparece antes en el menú.</div>
            </div>

            <div class="col-12">
              <label class="form-label" for="resumen">Resumen</label>
              <textarea class="form-control" id="resumen" name="resumen" rows="2">{{ old('resumen', $pagina->resumen) }}</textarea>
              <div class="form-text">Una o dos frases. Se usa arriba de la página y como respaldo de la descripción para Google.</div>
            </div>

            <div class="col-12">
              <label class="form-label" for="contenido">Contenido</label>
              <textarea class="form-control font-monospace" id="contenido" name="contenido" rows="14">{{ old('contenido', $pagina->contenido) }}</textarea>
              <div class="form-text">
                Admite HTML sencillo: <code>&lt;h2&gt;</code> para los subtítulos, <code>&lt;p&gt;</code> para párrafos
                y <code>&lt;ul&gt;&lt;li&gt;</code> para listas. Usa <code>&lt;h2&gt;</code> y no negritas para los
                apartados: Google los lee para entender de qué trata la página.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow mb-4">
        <div class="card-body">
          <h5 class="mb-1">Cómo se ve en Google</h5>
          <p class="text-muted small">
            Si dejas estos campos vacíos se calculan solos desde el título y el resumen, pero escribirlos
            es mejor: aquí decides la primera frase que lee alguien que todavía no ha entrado.
          </p>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="seo_titulo">Título en Google</label>
              <input type="text" class="form-control" id="seo_titulo" name="seo_titulo" maxlength="60"
                     value="{{ old('seo_titulo', $pagina->seo_titulo) }}">
              <div class="form-text">Máximo 60 caracteres: más largo se corta por el final, justo donde va la ciudad.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="seo_palabra_clave">Búsqueda a la que apunta</label>
              <input type="text" class="form-control" id="seo_palabra_clave" name="seo_palabra_clave"
                     value="{{ old('seo_palabra_clave', $pagina->seo_palabra_clave) }}"
                     placeholder="instalación de cámaras de seguridad en bogotá">
              <div class="form-text">Para referencia interna: qué escribe en Google quien debería llegar aquí.</div>
            </div>

            <div class="col-12">
              <label class="form-label" for="seo_descripcion">Descripción en Google</label>
              <textarea class="form-control" id="seo_descripcion" name="seo_descripcion" rows="2"
                        maxlength="155">{{ old('seo_descripcion', $pagina->seo_descripcion) }}</textarea>
              <div class="form-text">Máximo 155 caracteres. Di qué se ofrece, dónde, y cierra invitando a contactar.</div>
            </div>

            <div class="col-md-8">
              <label class="form-label" for="seo_og_imagen">Imagen al compartir (URL)</label>
              <input type="text" class="form-control" id="seo_og_imagen" name="seo_og_imagen"
                     value="{{ old('seo_og_imagen', $pagina->seo_og_imagen) }}">
              <div class="form-text">La que se ve al pegar el enlace en WhatsApp. Si se deja vacía se usa el logo.</div>
            </div>

            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="seo_noindex" name="seo_noindex" value="1"
                       @checked(old('seo_noindex', $pagina->seo_noindex))>
                <label class="form-check-label" for="seo_noindex">Ocultar esta página de Google</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow mb-4">
        <div class="card-body">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                   @checked(old('activo', $pagina->activo ?? true))>
            <label class="form-check-label" for="activo">Página publicada</label>
          </div>
          <div class="form-text">Sin marcar queda como borrador: no es visible ni entra al mapa del sitio.</div>
        </div>
      </div>

      <button class="btn btn-primary">Guardar</button>
      <a class="btn btn-outline-secondary" href="{{ route('sitio.admin.paginas') }}">Cancelar</a>
    </form>
  </div>
</x-app-layout>
