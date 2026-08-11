{{--
    Pantalla de carga con la animación del logo.

    Reglas que no se negocian, porque una pantalla de carga mal hecha es peor
    que no tener ninguna:

    - Se enseña UNA vez por visita (lo decide el script del <head>).
    - Nunca deja el sitio secuestrado. Hay tres salidas: el video termina, el
      tope duro de JavaScript, y —si el JavaScript no llegara a correr— una
      animación de CSS que la retira sola a los 6 segundos.
    - Se puede saltar con un clic o con Escape.
    - Con prefers-reduced-motion no aparece.

    El video original dura 10 segundos; aquí va recortado al tramo final
    —chispas, destello y aterrizaje del logo en reposo— y se reproduce algo
    acelerado: unos 3 segundos en total.
--}}
<div class="cargador" id="cargador" role="status" aria-label="Cargando">
  <div class="cargador__placa">
    <video class="cargador__video" id="cargadorVideo"
           muted playsinline preload="auto" tabindex="-1" aria-hidden="true">
      <source src="{{ asset('videos/innpro-logo.mp4') }}" type="video/mp4">
    </video>
  </div>
  <div class="cargador__barra"><i></i></div>
  <button type="button" class="cargador__saltar" id="cargadorSaltar">Saltar</button>
</div>
