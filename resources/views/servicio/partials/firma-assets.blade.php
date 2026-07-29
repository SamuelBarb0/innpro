{{-- Estilos + lógica del colector de firmas. Se incluye una sola vez por página. --}}
<style>
    .firma-block {
        border: 1px dashed rgba(36, 29, 94, .25);
        border-radius: 12px;
        padding: .85rem;
        background: #fff;
    }
    .firma-block-head {
        font-size: .82rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--in-indigo, #241D5E);
        margin-bottom: .6rem;
    }
    .firma-pad {
        position: relative;
        border: 1px solid rgba(36, 29, 94, .18);
        border-radius: 10px;
        background: repeating-linear-gradient(0deg, #fbfcfe, #fbfcfe 168px, rgba(36, 29, 94, .18) 169px, rgba(36, 29, 94, .18) 170px);
        overflow: hidden;
    }
    .firma-pad canvas {
        display: block;
        width: 100%;
        height: 170px;
        touch-action: none;   /* evita que el gesto de firmar haga scroll en móvil/tablet */
        cursor: crosshair;
    }
    .firma-pad-hint {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        color: #b3b8c8;
        font-size: .9rem;
        pointer-events: none;
        user-select: none;
    }
    .firma-pad-clear {
        position: absolute;
        top: 2px;
        right: 4px;
        text-decoration: none;
        color: #8a8f9c;
        font-size: .78rem;
    }
    .firma-guardada img {
        display: block;
        width: 100%;
        max-height: 150px;
        object-fit: contain;
        border-bottom: 1px solid #6b7185;
        padding-bottom: 4px;
        background: #fff;
    }
    .firma-datos { margin-top: .4rem; font-size: .88rem; }
</style>

<script>
(function () {
    if (window.__firmaPadListo) return;
    window.__firmaPadListo = true;

    function iniciarPad(contenedor) {
        var canvas = contenedor.querySelector('canvas');
        var hint   = contenedor.querySelector('.firma-pad-hint');
        var form   = contenedor.closest('[data-firma-form]');
        var input  = form ? form.querySelector('[data-firma-input]') : null;
        var ctx    = canvas.getContext('2d');
        var dibujando = false;
        // Guardamos los trazos (en píxeles CSS) para poder repintarlos de forma
        // SÍNCRONA al redimensionar. Restaurar desde una imagen sería asíncrono y,
        // si el usuario envía el formulario antes (p.ej. al abrirse el teclado de
        // una tablet), se guardaría una firma en blanco.
        var trazos = [];

        function hayTrazo() {
            return trazos.some(function (t) { return t.length > 1; });
        }

        function repintar() {
            var ancho = canvas.clientWidth || contenedor.clientWidth;
            ctx.clearRect(0, 0, ancho, 170);
            ctx.lineWidth   = 2.2;
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
            ctx.strokeStyle = '#1b2559';

            trazos.forEach(function (t) {
                if (t.length < 2) return;
                ctx.beginPath();
                ctx.moveTo(t[0].x, t[0].y);
                for (var i = 1; i < t.length; i++) ctx.lineTo(t[i].x, t[i].y);
                ctx.stroke();
            });
        }

        function ajustar() {
            var ratio = window.devicePixelRatio || 1;
            var ancho = contenedor.clientWidth;

            canvas.width        = Math.round(ancho * ratio);
            canvas.height       = Math.round(170 * ratio);
            canvas.style.height = '170px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            repintar();
        }

        function punto(e) {
            var r = canvas.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        }

        canvas.addEventListener('pointerdown', function (e) {
            dibujando = true;
            trazos.push([punto(e)]);
            try { canvas.setPointerCapture(e.pointerId); } catch (_) {}
        });

        canvas.addEventListener('pointermove', function (e) {
            if (!dibujando) return;
            e.preventDefault();
            trazos[trazos.length - 1].push(punto(e));
            repintar();
            if (hint && hayTrazo()) hint.style.display = 'none';
        });

        ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) {
            canvas.addEventListener(ev, function () { dibujando = false; });
        });

        var limpiar = contenedor.querySelector('[data-firma-clear]');
        if (limpiar) {
            limpiar.addEventListener('click', function () {
                trazos = [];
                repintar();
                if (hint) hint.style.display = '';
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                if (!hayTrazo()) {
                    e.preventDefault();
                    if (window.Swal) {
                        Swal.fire({ icon: 'warning', title: 'Falta la firma', text: 'Dibuja la firma en el recuadro antes de guardar.' });
                    } else {
                        alert('Dibuja la firma en el recuadro antes de guardar.');
                    }
                    return;
                }
                // Exportamos al tamaño CSS (no al de retina) para no enviar un PNG enorme,
                // y sobre fondo blanco: DomPDF no compone el canal alfa y la firma
                // saldría invisible en el PDF.
                repintar();
                var salida = document.createElement('canvas');
                salida.width  = canvas.clientWidth;
                salida.height = canvas.clientHeight;
                var sctx = salida.getContext('2d');
                sctx.fillStyle = '#ffffff';
                sctx.fillRect(0, 0, salida.width, salida.height);
                sctx.drawImage(canvas, 0, 0, salida.width, salida.height);
                input.value = salida.toDataURL('image/png');
            });
        }

        ajustar();
        window.addEventListener('resize', ajustar);
    }

    function iniciarTodos() {
        document.querySelectorAll('[data-firma-pad]').forEach(iniciarPad);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarTodos);
    } else {
        iniciarTodos();
    }
})();
</script>
