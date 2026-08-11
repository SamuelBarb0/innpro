<x-app-layout>
  <x-slot name="header">Sitio web · Portada</x-slot>

  <div class="container py-4" style="max-width:1100px">
    @include('sitio_admin.partials.avisos')
    @include('sitio_admin.partials.pestanas')

    {{-- Qué es esta pantalla, antes de soltar seis formularios encima. --}}
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
          <div>
            <h5 class="mb-1">La página de inicio, de arriba abajo</h5>
            <p class="text-muted mb-0" style="max-width:62ch">
              Cada bloque de abajo es una franja de la portada, en el mismo orden en que se ven.
              Los cambios salen publicados en cuanto guardas: no hay que avisar a nadie.
            </p>
          </div>
          <a class="btn btn-primary" href="{{ route('landing') }}" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right me-1"></i> Ver la portada
          </a>
        </div>

        {{-- Índice: de un vistazo, qué hay y cuánto. Y sirve para saltar. --}}
        <hr class="my-3">
        <div class="d-flex flex-wrap gap-2">
          @foreach ($portada->bloques as $i => $b)
            @php $e = App\Support\PortadaEsquema::de($b->clave); @endphp
            <a href="#bloque-{{ $b->id }}"
               class="btn btn-sm {{ $b->activo ? 'btn-outline-secondary' : 'btn-outline-danger' }}">
              <span class="text-muted me-1">{{ $i + 1 }}</span>{{ $e['nombre'] }}
              @unless ($b->activo)<i class="bi bi-eye-slash ms-1"></i>@endunless
            </a>
          @endforeach
        </div>
      </div>
    </div>

    @forelse ($portada->bloques as $indice => $bloque)
      @php
        $e       = App\Support\PortadaEsquema::de($bloque->clave);
        $datos   = $bloque->datos ?? [];
        $anclaWeb = $e['ancla'] ? route('landing').'#'.$e['ancla'] : route('landing');
      @endphp

      <div class="card shadow-sm mb-4" id="bloque-{{ $bloque->id }}">
        <div class="card-header bg-white py-3">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-secondary">{{ $indice + 1 }}</span>
                <h6 class="mb-0 fw-semibold">{{ $e['nombre'] }}</h6>
                @unless ($bloque->activo)
                  <span class="badge text-bg-danger">Oculta en la web</span>
                @endunless
              </div>
              @if (! empty($e['donde']))
                <div class="small text-muted mt-1">{{ $e['donde'] }}</div>
              @endif
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="{{ $anclaWeb }}" target="_blank" rel="noopener">
              <i class="bi bi-eye me-1"></i> Verla en la web
            </a>
          </div>
        </div>

        <div class="card-body">
          @if (! empty($e['ayuda']))
            <div class="alert alert-light border small d-flex gap-2 mb-4">
              <i class="bi bi-info-circle text-primary mt-1"></i>
              <div>{{ $e['ayuda'] }}</div>
            </div>
          @endif

          <form method="POST" action="{{ route('sitio.admin.secciones.guardar', $bloque) }}" data-editor>
            @csrf

            {{-- ── Textos del bloque ── --}}
            <div class="row g-3">
              @foreach ($e['campos'] ?? [] as $campo => $def)
                <div class="col-12">
                  @include('sitio_admin.partials.campo', [
                    'nombre' => $campo,
                    'def'    => $def,
                    'valor'  => $bloque->{$campo},
                  ])
                </div>
              @endforeach
            </div>

            {{-- ── Botones ── --}}
            @if (! empty($e['grupos']))
              <div class="row g-3 mt-1">
                @foreach ($e['grupos'] as $clave => $grupo)
                  @php $g = $bloque->grupo($clave); @endphp
                  <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                      <div class="fw-semibold small mb-1">{{ $grupo['nombre'] }}</div>
                      @if (! empty($grupo['ayuda']))
                        <div class="text-muted small mb-2">{{ $grupo['ayuda'] }}</div>
                      @endif
                      <div class="mb-2">
                        @include('sitio_admin.partials.campo', [
                          'nombre' => "datos[$clave][texto]",
                          'def'    => ['etiqueta' => 'Texto del botón'],
                          'valor'  => $g['texto'] ?? '',
                        ])
                      </div>
                      @include('sitio_admin.partials.campo', [
                        'nombre' => "datos[$clave][url]",
                        'def'    => ['etiqueta' => 'A dónde lleva', 'ayuda' => 'Una dirección completa, o #contacto para bajar a esa sección.'],
                        'valor'  => $g['url'] ?? '',
                      ])
                    </div>
                  </div>
                @endforeach
              </div>
            @endif

            {{-- ── Listas: tarjetas, cifras, viñetas ── --}}
            @foreach ($e['listas'] ?? [] as $clave => $lista)
              @php $filas = $bloque->lista($clave); @endphp

              <div class="mt-4 pt-3 border-top" data-lista="{{ $clave }}" data-max="{{ $lista['max'] ?? 50 }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                  <div class="fw-semibold">{{ $lista['nombre'] }}</div>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-anadir>
                    <i class="bi bi-plus-lg me-1"></i> Añadir {{ $lista['singular'] ?? 'elemento' }}
                  </button>
                </div>
                @if (! empty($lista['ayuda']))
                  <div class="text-muted small mb-3">{{ $lista['ayuda'] }}</div>
                @endif

                <div data-filas>
                  @foreach ($filas as $i => $fila)
                    <div class="border rounded-3 p-3 mb-2 bg-light-subtle" data-fila>
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-bg-light border" data-numero>{{ $i + 1 }}</span>
                        <div class="btn-group btn-group-sm">
                          <button type="button" class="btn btn-outline-secondary" data-subir title="Subir"><i class="bi bi-arrow-up"></i></button>
                          <button type="button" class="btn btn-outline-secondary" data-bajar title="Bajar"><i class="bi bi-arrow-down"></i></button>
                          <button type="button" class="btn btn-outline-danger" data-quitar title="Quitar"><i class="bi bi-trash"></i></button>
                        </div>
                      </div>
                      <div class="row g-2">
                        @foreach ($lista['campos'] as $campo => $def)
                          <div class="col-md-{{ $def['col'] ?? 12 }}">
                            @include('sitio_admin.partials.campo', [
                              'nombre' => "datos[$clave][$i][$campo]",
                              'def'    => $def,
                              'valor'  => $fila[$campo] ?? '',
                            ])
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @endforeach
                </div>

                @if (empty($filas))
                  <div class="text-muted small fst-italic" data-vacio>
                    Todavía no hay ninguna. Usa «Añadir {{ $lista['singular'] ?? 'elemento' }}».
                  </div>
                @endif

                {{-- El molde de una fila nueva. Va dentro de <template> para que
                     el navegador no lo trate como parte del formulario: los
                     inputs de aquí dentro no se envían. __i__ lo sustituye el
                     JavaScript por el número que toque. --}}
                <template data-molde>
                  <div class="border rounded-3 p-3 mb-2 bg-light-subtle" data-fila>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="badge text-bg-light border" data-numero></span>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-subir title="Subir"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-bajar title="Bajar"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" class="btn btn-outline-danger" data-quitar title="Quitar"><i class="bi bi-trash"></i></button>
                      </div>
                    </div>
                    <div class="row g-2">
                      @foreach ($lista['campos'] as $campo => $def)
                        <div class="col-md-{{ $def['col'] ?? 12 }}">
                          @include('sitio_admin.partials.campo', [
                            'nombre' => "datos[$clave][__i__][$campo]",
                            'def'    => $def,
                            'valor'  => '',
                          ])
                        </div>
                      @endforeach
                    </div>
                  </div>
                </template>
              </div>
            @endforeach

            {{-- ── Guardar ── --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="activo-{{ $bloque->id }}"
                       name="activo" value="1" @checked($bloque->activo)>
                <label class="form-check-label small" for="activo-{{ $bloque->id }}">
                  Mostrar esta sección en la web
                </label>
              </div>
              <button class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Guardar {{ mb_strtolower($e['nombre']) }}
              </button>
            </div>
          </form>
        </div>
      </div>
    @empty
      <div class="alert alert-warning">La portada no tiene secciones cargadas.</div>
    @endforelse
  </div>

  @push('scripts')
  <script>
  /* Editor de listas: añadir, quitar y reordenar tarjetas sin tocar código.
     Vanilla, que el panel no monta ningún framework.

     La clave está en renumerar: los inputs llevan el índice en el nombre
     (datos[tarjetas][2][titulo]), así que mover una fila de sitio en el DOM no
     basta —hay que reescribir los nombres— o al guardar saldría en el orden de
     antes. Por eso cualquier cambio pasa por renumerar(). */
  (function(){
    document.querySelectorAll('[data-lista]').forEach(function(bloque){
      var clave  = bloque.dataset.lista;
      var max    = parseInt(bloque.dataset.max, 10) || 50;
      var cajon  = bloque.querySelector('[data-filas]');
      var molde  = bloque.querySelector('[data-molde]');
      var anadir = bloque.querySelector('[data-anadir]');
      var vacio  = bloque.querySelector('[data-vacio]');

      function renumerar(){
        var filas = cajon.querySelectorAll('[data-fila]');
        filas.forEach(function(fila, i){
          fila.querySelectorAll('[name]').forEach(function(campo){
            campo.name = campo.name.replace(
              new RegExp('^datos\\[' + clave + '\\]\\[[^\\]]*\\]'),
              'datos[' + clave + '][' + i + ']'
            );
          });
          var n = fila.querySelector('[data-numero]');
          if (n) n.textContent = i + 1;
        });
        if (vacio) vacio.hidden = filas.length > 0;
        anadir.disabled = filas.length >= max;
        anadir.title = filas.length >= max ? 'Ya están todas las que caben (' + max + ')' : '';
      }

      anadir.addEventListener('click', function(){
        if (cajon.querySelectorAll('[data-fila]').length >= max) return;
        var nueva = molde.content.cloneNode(true);
        cajon.appendChild(nueva);
        renumerar();
        var ultima = cajon.lastElementChild;
        ultima.querySelector('input,textarea,select').focus();
        ultima.scrollIntoView({block:'nearest', behavior:'smooth'});
      });

      bloque.addEventListener('click', function(e){
        var fila = e.target.closest('[data-fila]');
        if (!fila || !cajon.contains(fila)) return;

        if (e.target.closest('[data-quitar]')){
          /* Sin confirmación: se puede volver a añadir, y todavía no se ha
             guardado nada. Pedir permiso para algo reversible solo estorba. */
          fila.remove();
          renumerar();
        } else if (e.target.closest('[data-subir]') && fila.previousElementSibling){
          fila.parentNode.insertBefore(fila, fila.previousElementSibling);
          renumerar();
        } else if (e.target.closest('[data-bajar]') && fila.nextElementSibling){
          fila.parentNode.insertBefore(fila.nextElementSibling, fila);
          renumerar();
        }
      });

      renumerar();
    });

    /* Aviso al salir con cambios sin guardar: son seis formularios en una
       misma pantalla y es fácil rellenar uno y darle a guardar en otro. */
    var sucio = false;
    document.querySelectorAll('form[data-editor]').forEach(function(f){
      f.addEventListener('input', function(){ sucio = true; });
      f.addEventListener('submit', function(){ sucio = false; });
    });
    window.addEventListener('beforeunload', function(e){
      if (sucio){ e.preventDefault(); e.returnValue = ''; }
    });
  })();
  </script>
  @endpush
</x-app-layout>
