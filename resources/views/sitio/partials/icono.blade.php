{{--
    Iconos de las tarjetas de servicio.

    Van como SVG en línea y no como imágenes a propósito: son parte del diseño,
    pesan nada y se recolorean con la paleta. Además evita cuatro peticiones más
    en la portada, que es donde la velocidad de carga cuenta.

    `nombre` elige el icono; si viene vacío se usa `indice` para conservar el
    orden histórico de la portada (llave, escudo, alturas).
--}}
@php
    $iconos = ['llave', 'escudo', 'alturas', 'camara', 'acceso', 'incendio'];
    $elegido = $nombre ?: ($iconos[$indice ?? 0] ?? 'escudo');
@endphp

@switch($elegido)
    @case('llave')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 01-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 015.4-5.4l-2.5 2.5"/></svg>
        @break

    @case('escudo')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 4v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
        @break

    @case('alturas')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v7"/><circle cx="12" cy="12" r="2.2"/><path d="M12 14v7M5 21l7-7 7 7"/></svg>
        @break

    @case('camara')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8.5l14-3.5 1.4 5.4-14 3.5z"/><path d="M6 13v5M4 21h6"/><circle cx="20" cy="9" r="2"/></svg>
        @break

    @case('acceso')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a5 5 0 015 5v1.5"/><path d="M7 9V8a5 5 0 013-4.6"/><path d="M5 12c0-1.2.5-2.3 1.4-3"/><path d="M9 21c-1.4-2-2-4-2-6a5 5 0 0110 0c0 1-.2 2-.5 3"/><path d="M12 12v4"/></svg>
        @break

    @case('incendio')
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2s4 4.5 4 8a4 4 0 01-8 0c0-1.2.5-2.3 1.2-3.2"/><path d="M9 16c0 2 1.3 4 3 4s3-2 3-4"/></svg>
        @break

    @default
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>
@endswitch
