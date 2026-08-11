{{--
    Interruptor de tema, dibujado como el diafragma del lente que da nombre
    al resto de la portada: abierto deja pasar la luz (tema claro), cerrado la
    bloquea (tema oscuro). No es un sol y una luna genéricos porque el motivo
    del sitio entero es el lente de la cámara del logo.

    El botón se pinta ya en el estado correcto porque el <html> trae
    `data-tema` desde el script que corre en el <head>, antes de la primera
    pintura. Sin eso se vería un parpadeo del tema equivocado en cada carga.
--}}
<button type="button"
        class="tema js-tema {{ $extra ?? '' }}"
        aria-label="Cambiar entre tema claro y oscuro"
        title="Cambiar tema">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false">
    <circle cx="12" cy="12" r="9"/>
    <circle class="tema__iris" cx="12" cy="12" r="5.2" fill="currentColor" stroke="none"/>
    <g class="tema__hoja">
      <path d="M12 1.5v2"/><path d="M12 20.5v2"/>
      <path d="M1.5 12h2"/><path d="M20.5 12h2"/>
      <path d="M4.6 4.6l1.4 1.4"/><path d="M18 18l1.4 1.4"/>
      <path d="M19.4 4.6L18 6"/><path d="M6 18l-1.4 1.4"/>
    </g>
  </svg>
</button>
