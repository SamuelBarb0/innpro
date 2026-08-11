{{--
    Este script tiene que ir en el <head> y SIN defer, aunque bloquee: es lo
    único que evita el parpadeo. Si el tema se aplicara desde el script del
    final del documento, cada carga pintaría primero el tema por defecto y
    después saltaría al elegido —un fogonazo blanco en mitad de la cara de
    quien navega de noche.

    Por defecto el sitio abre en CLARO. Solo se respeta otra cosa si la
    persona ya eligió: su decisión manda sobre la del sistema operativo,
    porque eligió estando aquí.

    Aquí mismo se decide si toca enseñar la animación del logo. Va en el
    <head> por el mismo motivo: si se decidiera abajo, quien ya la vio
    alcanzaría a ver el destello de la pantalla de carga antes de que
    desapareciera.
--}}
<script>
(function(){
  var raiz = document.documentElement;

  var guardado = null;
  try { guardado = localStorage.getItem('innpro-tema'); } catch (e) {}
  raiz.setAttribute('data-tema',
    (guardado === 'claro' || guardado === 'oscuro') ? guardado : 'claro');

  /* La animación del logo se enseña UNA vez por visita, no en cada página:
     verla otra vez al abrir un servicio desde el menú sería un peaje. */
  var visto = null;
  try { visto = sessionStorage.getItem('innpro-cargador'); } catch (e) {}
  if (visto) {
    raiz.classList.add('sin-cargador');
  } else {
    /* Mientras la carga tapa la pantalla, la entrada del hero espera: si no,
       el iris se abriría detrás del logo y nadie vería la pieza principal.
       Es un retardo de CSS y no un `paused` a propósito —si el script del
       final no llegara a correr, el hero aparece igual con unos segundos de
       más en vez de quedarse invisible para siempre. */
    raiz.classList.add('cargando');
  }
})();
</script>
