/**
 * Selección múltiple y acciones en lote para las tablas DataTables del panel.
 *
 * Se engancha a una tabla ya inicializada y se encarga de:
 *   - recordar lo seleccionado aunque se cambie de página, se busque o se
 *     reordene (DataTables repinta las filas en cada draw, así que las casillas
 *     hay que volver a marcarlas contra un Set de ids);
 *   - mostrar la barra de acciones solo cuando hay algo seleccionado;
 *   - ofrecer "seleccionar los N que coinciden con el filtro", pidiéndole los
 *     ids al servidor con los MISMOS parámetros que la tabla está usando, para
 *     que nunca se actúe sobre filas distintas de las que el usuario ve;
 *   - confirmar antes de lo destructivo y contar lo que realmente pasó.
 *
 * Uso:
 *   AccionesEnLote({
 *     tabla: table,                       // instancia de DataTable
 *     selector: '#productos-table',
 *     url: '/productos/lote',             // POST {accion, ids}
 *     urlIds: '/productos',               // GET  con los params de la tabla + solo_ids=1
 *     etiqueta: { singular:'producto', plural:'productos' },
 *     acciones: [
 *       { clave:'activar',    texto:'Activar',    clase:'btn-outline-success' },
 *       { clave:'desactivar', texto:'Desactivar', clase:'btn-outline-warning' },
 *       { clave:'eliminar',   texto:'Eliminar',   clase:'btn-outline-danger', destructiva:true },
 *     ],
 *   });
 */
window.AccionesEnLote = function (config) {
  // Las tablas del panel usan `language: { url: ... }`, y con eso DataTables
  // APLAZA la inicialización hasta que llega el JSON del idioma. Si nos
  // enganchamos antes, todavía no hay contenedor: `$(undefined).before(...)` no
  // hace nada y tampoco lanza error, así que la barra desaparecía en silencio.
  let contenedor = null;
  try { contenedor = config.tabla.table().container(); } catch (e) { contenedor = null; }

  if (!contenedor) {
    config.tabla.one('init.dt', () => window.AccionesEnLote(config));
    return;
  }

  iniciarAccionesEnLote(config, contenedor);
};

function iniciarAccionesEnLote(config, contenedor) {
  const tabla     = config.tabla;
  const $tabla    = $(config.selector);
  const etiqueta  = config.etiqueta || { singular: 'fila', plural: 'filas' };
  const acciones  = config.acciones || [];
  const csrf      = $('meta[name="csrf-token"]').attr('content') || config.csrf;

  const seleccion = new Set();   // ids elegidos, sobreviven a los repintados

  // Cuántas filas coinciden con el filtro actual. Se toma ya mismo y no solo en
  // el `draw`: cuando la tabla se inicializa en diferido, el primer draw ya
  // ocurrió antes de que nos enganchemos y el contador se quedaba en cero (con
  // lo que el enlace de "seleccionar todos" nunca aparecía).
  const infoInicial = tabla.page.info();
  let totalFiltrado = infoInicial ? infoInicial.recordsDisplay : 0;

  // Con `scrollX` DataTables CLONA el encabezado en un contenedor aparte, y el
  // que ve el usuario es el clon: si nos colgamos del <table> original, la
  // casilla de "toda la página" no dispara nada. Por eso el contenedor manda
  // para todo lo que viva en el encabezado; el cuerpo sí sigue en la tabla.
  const $contenedor = $(contenedor);

  // ---------------------------------------------------------------- barra
  const idBarra = config.selector.replace(/[^a-zA-Z0-9]/g, '') + '-barra-lote';
  $contenedor.before(`
    <div id="${idBarra}" class="alert alert-secondary d-none align-items-center flex-wrap gap-2 py-2 px-3 mb-2">
      <span class="conteo fw-semibold"></span>
      <a href="#" class="ampliar small"></a>
      <span class="flex-grow-1"></span>
      <span class="botones d-flex flex-wrap gap-1"></span>
      <button type="button" class="btn btn-sm btn-link text-muted limpiar">Quitar selección</button>
    </div>`);

  const $barra = $('#' + idBarra);

  $barra.find('.botones').html(
    acciones.map(a =>
      `<button type="button" class="btn btn-sm ${a.clase || 'btn-outline-dark'}" data-accion="${a.clave}">${a.texto}</button>`
    ).join('')
  );

  function pintarBarra() {
    const n = seleccion.size;
    if (!n) { $barra.addClass('d-none').removeClass('d-flex'); return; }

    $barra.removeClass('d-none').addClass('d-flex');
    $barra.find('.conteo').text(
      `${n} ${n === 1 ? etiqueta.singular : etiqueta.plural} seleccionado${n === 1 ? '' : 's'}.`
    );

    // El enlace para ampliar solo aparece si de verdad hay más coincidencias
    // fuera de lo ya seleccionado.
    const $ampliar = $barra.find('.ampliar');
    if (config.urlIds && totalFiltrado > n) {
      $ampliar.text(`Seleccionar los ${totalFiltrado.toLocaleString('es-CO')} que coinciden con el filtro`).show();
    } else {
      $ampliar.hide();
    }
  }

  // ------------------------------------------------------- casillas y draw
  tabla.on('draw.dt', function () {
    const info = tabla.page.info();
    totalFiltrado = info ? info.recordsDisplay : 0;

    $tabla.find('.fila-lote').each(function () {
      this.checked = seleccion.has(parseInt(this.value, 10));
    });
    sincronizarCabecera();
    pintarBarra();
  });

  function sincronizarCabecera() {
    const $filas = $tabla.find('.fila-lote');
    const marcadas = $filas.filter(':checked').length;
    // Todas las copias del encabezado (original + clon de scrollX).
    const $cab = $contenedor.find('.lote-todas');
    $cab.prop('checked', $filas.length > 0 && marcadas === $filas.length);
    $cab.prop('indeterminate', marcadas > 0 && marcadas < $filas.length);
  }

  $tabla.on('change', '.fila-lote', function () {
    const id = parseInt(this.value, 10);
    this.checked ? seleccion.add(id) : seleccion.delete(id);
    sincronizarCabecera();
    pintarBarra();
  });

  // La casilla del encabezado marca SOLO la página visible. Lo masivo exige un
  // clic aparte, para que nadie borre miles de filas creyendo que eran 24.
  $contenedor.on('change', '.lote-todas', function () {
    const marcar = this.checked;
    $tabla.find('.fila-lote').each(function () {
      this.checked = marcar;
      const id = parseInt(this.value, 10);
      marcar ? seleccion.add(id) : seleccion.delete(id);
    });
    sincronizarCabecera();
    pintarBarra();
  });

  // ------------------------------------------------- seleccionar todo el filtro
  $barra.on('click', '.ampliar', function (e) {
    e.preventDefault();
    const $enlace = $(this);
    const textoOriginal = $enlace.text();
    $enlace.text('Buscando…');

    // Los MISMOS parámetros que la tabla está usando ahora: misma búsqueda,
    // mismo orden, mismas columnas.
    const params = $.extend({}, tabla.ajax.params(), { solo_ids: 1, start: 0, length: -1 });

    $.get(config.urlIds, params)
      .done(resp => {
        (resp.ids || []).forEach(id => seleccion.add(parseInt(id, 10)));
        $tabla.find('.fila-lote').each(function () {
          this.checked = seleccion.has(parseInt(this.value, 10));
        });
        sincronizarCabecera();
        pintarBarra();
      })
      .fail(xhr => {
        $enlace.text(textoOriginal);
        avisar('No se pudo ampliar la selección' + (xhr.status ? ` (error ${xhr.status})` : '') + '.', 'warning');
      });
  });

  $barra.on('click', '.limpiar', function () {
    seleccion.clear();
    $tabla.find('.fila-lote').prop('checked', false);
    $contenedor.find('.lote-todas').prop('checked', false).prop('indeterminate', false);
    pintarBarra();
  });

  // ----------------------------------------------------------- ejecutar
  $barra.on('click', '[data-accion]', function () {
    const clave  = $(this).data('accion');
    const accion = acciones.find(a => a.clave === clave);
    const ids    = Array.from(seleccion);
    if (!ids.length) return;

    const cuantas = `${ids.length} ${ids.length === 1 ? etiqueta.singular : etiqueta.plural}`;
    if (accion && accion.destructiva) {
      if (!confirm(`Vas a ${accion.texto.toLowerCase()} ${cuantas}. ¿Continuar?`)) return;
    }

    const $botones = $barra.find('button, .ampliar');
    $botones.prop('disabled', true);

    $.ajax({
      url: config.url,
      method: 'POST',
      data: { _token: csrf, accion: clave, ids: ids },
    })
      .done(resp => {
        seleccion.clear();
        tabla.ajax.reload(null, false);
        avisar(resp.mensaje || 'Listo.', resp.omitidos ? 'warning' : 'success');
      })
      .fail(xhr => {
        const msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.mensaje))
          || `No se pudo completar la acción${xhr.status ? ` (error ${xhr.status})` : ''}.`;
        avisar(msg, 'danger');
      })
      .always(() => {
        $botones.prop('disabled', false);
        pintarBarra();
      });
  });

  function avisar(mensaje, tipo) {
    if (typeof window.mostrarNotificacion === 'function') {
      window.mostrarNotificacion(mensaje, tipo);
      return;
    }
    const $aviso = $(`<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>`);
    $barra.after($aviso);
    setTimeout(() => $aviso.alert('close'), 6000);
  }
}
