<x-app-layout>
  <x-slot name="header">Sitio web · Ajustes</x-slot>

  <div class="container py-4">
    @include('sitio_admin.partials.avisos')

    <div class="alert alert-info">
      <strong>Estos datos salen en tres sitios a la vez:</strong> en la página, en la ficha que lee Google
      y —cuando se configure— en el perfil de Google Business. Tienen que ser <strong>idénticos</strong> en los tres:
      una dirección escrita distinta en cada lado es de las cosas que más le cuesta a un negocio local
      aparecer en el mapa.
    </div>

    <form method="POST" action="{{ route('sitio.admin.ajustes.guardar') }}">
      @csrf

      @foreach($grupos as $grupo => $campos)
        <div class="card shadow mb-4">
          <div class="card-body">
            <h5 class="mb-3">{{ $grupo }}</h5>
            <div class="row g-3">
              @foreach($campos as $clave => $campo)
                <div class="col-md-6">
                  <label class="form-label" for="{{ $clave }}">{{ $campo['etiqueta'] }}</label>
                  <input type="text" class="form-control" id="{{ $clave }}" name="{{ $clave }}"
                         value="{{ old($clave, $campo['valor']) }}">
                  @if($campo['ayuda'])
                    <div class="form-text">{{ $campo['ayuda'] }}</div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach

      <div class="card shadow mb-4 border-warning">
        <div class="card-body">
          <h5 class="mb-2">Visibilidad en buscadores</h5>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="sitio_noindex" name="sitio_noindex" value="1"
                   @checked($noindex)>
            <label class="form-check-label" for="sitio_noindex">
              Ocultar TODO el sitio de Google
            </label>
          </div>
          <p class="text-muted small mb-0 mt-2">
            Márcalo solo mientras este sitio no sea el oficial del dominio. Si el sitio viejo sigue publicado
            con los mismos textos, tener los dos visibles hace que compitan entre sí y los dos posicionen peor.
            <strong>Acuérdate de desmarcarlo el día del cambio</strong>, o el sitio nuevo será invisible en Google.
          </p>
        </div>
      </div>

      <button class="btn btn-primary">Guardar ajustes</button>
      <a class="btn btn-outline-secondary" href="{{ route('landing') }}" target="_blank">Ver el sitio</a>
    </form>
  </div>
</x-app-layout>
