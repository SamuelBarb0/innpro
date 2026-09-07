{{--
    Los enlaces legales del pie.

    En un partial y no copiados en los tres pies porque el día que se añada una
    tercera página legal hay que tocar un archivo, no tres —y el que se olvida
    siempre es el tercero—. Se pintan solo si la página existe y está publicada:
    un enlace a una política que devuelve 404 es peor que no tenerlo.
--}}
@php
    $legales = \App\Models\SitioPagina::publicadas()
        ->deTipo(\App\Models\SitioPagina::LEGAL)
        ->orderBy('orden')
        ->get(['slug', 'titulo']);
@endphp

@if ($legales->isNotEmpty())
    <span class="foot__legal">
        @foreach ($legales as $legal)
            <a href="{{ url('/'.$legal->slug) }}">{{ $legal->titulo }}</a>
        @endforeach
        {{-- Reabre el aviso. Es un requisito real y no un adorno: quien
             rechazó tiene que poder cambiar de idea sin borrar el navegador. --}}
        <a href="#" data-ck-abrir>Preferencias de cookies</a>
    </span>
@endif
