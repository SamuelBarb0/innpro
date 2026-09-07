{{--
    Aviso de cookies.

    El cliente pidió arreglar el que sale en inglés; ese es el del tema Enfold
    del WordPress viejo, y este sitio no tenía ninguno. Este va escrito a mano,
    en español, sin librería ni servicio de terceros: son treinta líneas, y un
    gestor de consentimiento externo metería en el sitio justo lo que el aviso
    dice que no hay — scripts de terceros cargados antes de que nadie acepte.

    La elección se guarda en localStorage y NO en una cookie, para no tener que
    poner una cookie con el fin de recordar que no quiere cookies.

    El botón «Preferencias de cookies» del pie vuelve a abrirlo: rechazar tiene
    que ser tan reversible como aceptar, o no es una elección.
--}}
@php
    $politica = \App\Models\SitioPagina::publicadas()
        ->deTipo(\App\Models\SitioPagina::LEGAL)
        ->where('slug', 'politica-de-cookies')
        ->value('slug');
@endphp

<div class="ck" id="avisoCookies" hidden role="dialog" aria-live="polite"
     aria-label="Aviso de cookies">
  <div class="ck__txt">
    <strong>{{ \App\Support\Sitio::valor('sitio_cookies_titulo', 'Este sitio usa cookies.') }}</strong>
    {{ \App\Support\Sitio::valor('sitio_cookies_texto', 'Las necesarias para que funcione van siempre; las de medición, solo si usted lo acepta.') }}
    @if ($politica)
      <a href="{{ url('/'.$politica) }}">Ver la política de cookies</a>.
    @endif
  </div>
  <div class="ck__btns">
    <button type="button" class="ck__btn ck__btn--no" data-ck="rechazar">Rechazar</button>
    <button type="button" class="ck__btn ck__btn--si" data-ck="aceptar">Aceptar</button>
  </div>
</div>

<style>
  .ck{position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:900;
      display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between;
      max-width:62rem;margin-inline:auto;padding:1.05rem 1.25rem;
      background:var(--bg-elev,#fff);color:var(--fg,#101418);
      border:1px solid rgba(128,128,128,.28);border-radius:.7rem;
      box-shadow:0 18px 48px rgba(0,0,0,.22);font-size:.92rem;line-height:1.55}
  /* Sin esta línea el aviso NO se cierra: `display:flex` de la clase le gana
     al `display:none` que el navegador aplica por [hidden], así que el panel se
     quedaba puesto para siempre por mucho que se pulsara Aceptar. Se vio
     mirando la página, no en el código. */
  .ck[hidden]{display:none}
  .ck__txt{flex:1 1 22rem;min-width:0}
  .ck__txt a{text-decoration:underline;text-underline-offset:.18em}
  .ck__btns{display:flex;gap:.6rem;flex:0 0 auto}
  .ck__btn{padding:.6rem 1.15rem;border-radius:.45rem;font:inherit;font-weight:600;cursor:pointer;
           border:1px solid transparent;white-space:nowrap}
  /* Rechazar es un botón de verdad, con el mismo tamaño y peso que aceptar:
     esconderlo detrás de un enlace gris es la trampa que la norma persigue. */
  .ck__btn--no{background:transparent;border-color:rgba(128,128,128,.45);color:inherit}
  .ck__btn--si{background:var(--acc,#3488bd);color:#fff}
  .ck__btn:focus-visible{outline:2px solid var(--acc,#3488bd);outline-offset:2px}
  @media (max-width:520px){
    .ck{flex-direction:column;align-items:stretch}
    .ck__btns{justify-content:flex-end}
  }
  @media (prefers-reduced-motion:no-preference){
    .ck{animation:ckSube .32s ease-out both}
    @keyframes ckSube{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  }
</style>

<script>
(function(){
  'use strict';

  var LLAVE = 'innpro_cookies';
  var aviso = document.getElementById('avisoCookies');
  if (!aviso) return;

  /* localStorage puede lanzar excepción, no solo devolver null: en una ventana
     privada o con las cookies de sitio bloqueadas, el simple acceso revienta.
     Si eso tumbara este script, el aviso se quedaría escondido para siempre. */
  function leer(){
    try { return localStorage.getItem(LLAVE); } catch (e) { return null; }
  }
  function guardar(v){
    try { localStorage.setItem(LLAVE, v); } catch (e) { /* sesión sin almacenamiento */ }
  }

  function aplicar(decision){
    /* Consent Mode: gtag ya está cargado con analytics_storage en 'denied'
       (ver partials/seo.blade.php), así que aquí solo se levanta el permiso.
       Hacerlo al revés —cargar Analytics y luego apagarlo— ya habría dejado
       la cookie puesta. */
    if (typeof window.gtag === 'function') {
      window.gtag('consent', 'update', {
        analytics_storage: decision === 'aceptar' ? 'granted' : 'denied'
      });
    }
  }

  function decidir(decision){
    guardar(decision);
    aplicar(decision);
    aviso.hidden = true;
  }

  var previa = leer();

  if (previa === 'aceptar' || previa === 'rechazar') {
    aplicar(previa);
  } else {
    aviso.hidden = false;
  }

  aviso.addEventListener('click', function(e){
    var b = e.target.closest('[data-ck]');
    if (b) decidir(b.getAttribute('data-ck'));
  });

  /* El enlace del pie: volver a elegir sin tener que borrar el navegador. */
  document.addEventListener('click', function(e){
    var p = e.target.closest('[data-ck-abrir]');
    if (!p) return;
    e.preventDefault();
    aviso.hidden = false;
    aviso.querySelector('.ck__btn').focus();
  });
})();
</script>
