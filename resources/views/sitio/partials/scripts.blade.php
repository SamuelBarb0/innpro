{{--
    Todo el comportamiento del sitio público, compartido por la portada y las
    páginas de servicio. Cada bloque comprueba que sus elementos existan: en
    una página de servicio no hay lente ni contadores, y un `null.addEventListener`
    tumbaría de paso el menú móvil, que sí hace falta.

    Nada de librerías. Son ~200 líneas sin dependencias y sin build: el sitio
    lo sirve Blade tal cual, sin pasar por Vite.
--}}
<script>
(function(){
  'use strict';

  var raiz    = document.documentElement;
  var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var conRaton= window.matchMedia('(hover:hover) and (pointer:fine)').matches;
  var rico    = !reduce && conRaton;   // los adornos que siguen al cursor

  /* Salvavidas, lo primero de todo: la entrada del hero está en pausa
     esperando a que se retire la carga. Si algo de aquí abajo reventara, este
     temporizador —ya programado— la suelta igual y nadie se queda mirando un
     hero congelado. */
  setTimeout(function(){ raiz.classList.remove('cargando'); }, 5000);

  /* ==============================================================
     PANTALLA DE CARGA

     Tres salidas, y ninguna depende de que el video se porte bien:
     termina el video, se salta a mano, o salta el tope duro. La cuarta
     —la de CSS a los 6s— cubre el caso de que este script ni siquiera
     llegue a ejecutarse.
     ============================================================== */
  var cargador = document.getElementById('cargador');
  if (cargador){
    if (raiz.classList.contains('sin-cargador') || reduce){
      raiz.classList.remove('cargando');
      cargador.remove();
    } else {
      var vid      = document.getElementById('cargadorVideo');
      var cerrado  = false;
      var scrollAntes = document.body.style.overflow;
      document.body.style.overflow = 'hidden';

      var cerrar = function(){
        if (cerrado) return;
        cerrado = true;
        try { sessionStorage.setItem('innpro-cargador', 'visto'); } catch (e) {}
        raiz.classList.remove('cargando');          // suelta la entrada del hero
        cargador.classList.add('fuera');
        document.body.style.overflow = scrollAntes;
        setTimeout(function(){ cargador.remove(); }, 700);
      };

      if (vid){
        /* El recorte dura 3,1s; a 1.45x se queda en 2,1. Contando lo que el
           navegador tarda en arrancarlo, la carga entera ronda los 2,5s. Más
           que eso, en la web de una empresa, es peaje y no marca. */
        vid.playbackRate = 1.45;
        vid.addEventListener('ended', cerrar);
        vid.addEventListener('error', cerrar);
        var reproducir = vid.play();
        /* Si el navegador bloquea la reproducción, no se castiga a nadie con
           una pantalla congelada: se quita y a navegar. */
        if (reproducir && reproducir.catch) reproducir.catch(cerrar);
      } else {
        cerrar();
      }

      var saltar = document.getElementById('cargadorSaltar');
      if (saltar) saltar.addEventListener('click', cerrar);
      cargador.addEventListener('click', cerrar);
      document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' || e.key === 'Esc') cerrar();
      });

      setTimeout(cerrar, 4500);
    }
  } else {
    raiz.classList.remove('cargando');
  }

  /* ==============================================================
     VIDEO DE FONDO DEL HERO

     El src se pone aquí y no en el HTML: así el móvil no se baja 1,2 MB
     de video que no va a ver. Se queda con el cartel, que pesa 80 KB.
     ============================================================== */
  var hero = document.querySelector('.hero');
  var heroVid = document.getElementById('heroVideo');
  if (hero && heroVid){
    var ahorraDatos = navigator.connection && navigator.connection.saveData;
    var pantallaAncha = window.matchMedia('(min-width:981px)').matches;

    if (!reduce && pantallaAncha && !ahorraDatos){
      heroVid.addEventListener('loadeddata', function(){
        heroVid.classList.add('puesto');
        hero.classList.add('con-video');
      });
      /* El orden importa. El elemento nace con preload="none" para que el
         móvil no se baje nada; al decidir que SÍ toca, hay que subirlo a
         "auto" y llamar a load() antes de play(). Sin ese load(), con
         preload="none" el navegador no pide un solo byte y el video se queda
         esperando un `canplay` que no va a llegar nunca. */
      heroVid.preload = 'auto';
      heroVid.src = heroVid.dataset.src;
      heroVid.load();
      var pr = heroVid.play();
      if (pr && pr.catch) pr.catch(function(){});
    }
  }

  /* ==============================================================
     TEMA
     ============================================================== */
  document.querySelectorAll('.js-tema').forEach(function(btn){
    btn.addEventListener('click', function(){
      var nuevo = raiz.getAttribute('data-tema') === 'claro' ? 'oscuro' : 'claro';
      raiz.setAttribute('data-tema', nuevo);
      try { localStorage.setItem('innpro-tema', nuevo); } catch (e) {}
      /* El color de la barra del navegador en móvil también cambia; si no,
         queda una franja navy sobre un sitio blanco. */
      var meta = document.querySelector('meta[name="theme-color"]');
      if (meta) meta.setAttribute('content', nuevo === 'claro' ? '#eff2f9' : '#020B3C');
    });
  });

  /* ==============================================================
     NAV: sombra al hacer scroll + menú móvil
     ============================================================== */
  var nav    = document.getElementById('nav');
  var burger = document.getElementById('burger');
  var links  = document.getElementById('links');

  if (burger && links){
    burger.addEventListener('click', function(){
      var abierto = links.classList.toggle('open');
      burger.classList.toggle('open', abierto);
      burger.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
    links.addEventListener('click', function(e){
      if (e.target.closest('a')){
        burger.classList.remove('open');
        links.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ==============================================================
     SCROLL: barra de progreso, nav pegajoso, hilo de las secciones
     y marcado de la sección activa.

     Todo en UN solo listener con requestAnimationFrame. Cuatro
     listeners de scroll independientes es como se arruina el
     desplazamiento en un móvil de gama media.
     ============================================================== */
  var prog     = document.getElementById('prog');
  var enlaces  = [].slice.call(document.querySelectorAll('.nav__links a[href*="#"]'));
  var secciones= enlaces.map(function(a){
    var id = a.getAttribute('href').split('#')[1];
    return id ? document.getElementById(id) : null;
  });

  /* La regla que cose las secciones se dibuja UNA vez al entrar, con un
     IntersectionObserver, y no fotograma a fotograma.

     Antes se escribía una custom property (`--hilo`) sobre la propia sección
     en cada marco de scroll, y eso salió carísimo al medirlo: escribir una
     variable CSS en un elemento invalida el estilo de TODO su subárbol,
     porque cualquier descendiente podría estar leyéndola. Como dentro de esas
     secciones viven las tarjetas y los lineamientos, cada fotograma
     recalculaba media página: 1,1 segundos de recálculo de estilos en 4
     segundos de scroll. Se ve igual y ahora cuesta cero. */
  var ioHilo = new IntersectionObserver(function(entradas){
    entradas.forEach(function(en){
      if (en.isIntersecting){ en.target.classList.add('cosida'); ioHilo.unobserve(en.target); }
    });
  }, {threshold:.05});
  document.querySelectorAll('.sect--alt').forEach(function(s){ ioHilo.observe(s); });

  var recorrible = 0;
  function medirAlto(){
    recorrible = document.documentElement.scrollHeight - window.innerHeight;
  }
  medirAlto();

  var pedido = false;
  function alHacerScroll(){
    /* Primero TODAS las lecturas y después todas las escrituras. Mezclarlas
       obliga al navegador a recalcular la maquetación a mitad del bucle. */
    var y   = window.scrollY || window.pageYOffset;
    var alto = window.innerHeight;
    var activo = -1;
    for (var i = 0; i < secciones.length; i++){
      if (secciones[i] && secciones[i].getBoundingClientRect().top <= alto * 0.34) activo = i;
    }

    if (nav) nav.classList.toggle('is-stuck', y > 40);
    /* transform directo y no una variable CSS: es una propiedad que resuelve
       el compositor, sin tocar estilo ni maquetación. */
    if (prog) prog.style.transform = 'scaleX(' + (recorrible > 0 ? Math.min(y / recorrible, 1) : 0) + ')';
    for (var j = 0; j < enlaces.length; j++){
      enlaces[j].classList.toggle('is-here', j === activo);
    }

    pedido = false;
  }
  window.addEventListener('scroll', function(){
    if (!pedido){ pedido = true; requestAnimationFrame(alHacerScroll); }
  }, {passive:true});
  window.addEventListener('resize', function(){ medirAlto(); alHacerScroll(); }, {passive:true});
  alHacerScroll();

  /* ==============================================================
     CONGELAR LO QUE NO SE ESTÁ VIENDO

     Un navegador no detiene las animaciones de lo que queda fuera de la
     pantalla, ni pausa un video de fondo. En un monitor de 60 Hz da igual;
     en uno de 179, con 5,6 ms por fotograma, es la diferencia entre ir
     fluido y dar tirones.

     Tres cosas hay que parar a mano, porque cada una obedece a un amo
     distinto: las animaciones de CSS (con una clase), las de SVG —el
     trazado del circuito usa SMIL y se ríe de `animation-play-state`— y el
     video, que decodifica y compone aunque nadie lo mire.
     ============================================================== */
  var circuito = document.querySelector('.circuit');
  var ioQuieta = new IntersectionObserver(function(entradas){
    entradas.forEach(function(en){
      var fuera = !en.isIntersecting;
      en.target.classList.toggle('quieta', fuera);

      if (en.target === hero){
        if (circuito){
          if (fuera) circuito.pauseAnimations();
          else circuito.unpauseAnimations();
        }
        if (heroVid && heroVid.src){
          if (fuera) heroVid.pause();
          else { var pv = heroVid.play(); if (pv && pv.catch) pv.catch(function(){}); }
        }
      }
    });
  }, {threshold:0});
  ['.hero','.marquee','.about__art','.wa'].forEach(function(sel){
    var el = document.querySelector(sel);
    if (el) ioQuieta.observe(el);
  });

  /* ==============================================================
     REVELADO AL ENTRAR EN PANTALLA
     ============================================================== */
  /* Dos observadores por elemento, y el orden importa:

     El primero se dispara 400px ANTES de que el bloque asome y solo marca
     `lista`, que pone will-change. Así el navegador crea la capa mientras no
     pasa nada. El segundo es el que revela.

     Con un único observador, la promoción de capa caía en el mismo fotograma
     en que arrancaba la transición, y eso se notaba como un tropiezo por cada
     bloque que aparecía —unos veinte por recorrido—. `hecha` devuelve el
     will-change a su sitio: dejarlo puesto para siempre gasta memoria de GPU
     por algo que ya ocurrió. */
  function prepararYRevelar(selector, claseFinal){
    var elementos = document.querySelectorAll(selector);

    var ioPreparar = new IntersectionObserver(function(entradas){
      entradas.forEach(function(en){
        if (en.isIntersecting){ en.target.classList.add('lista'); ioPreparar.unobserve(en.target); }
      });
    }, {rootMargin:'400px 0px 400px 0px'});

    var ioRevelar = new IntersectionObserver(function(entradas){
      entradas.forEach(function(en){
        if (!en.isIntersecting) return;
        var el = en.target;
        el.classList.add('lista', claseFinal);
        ioRevelar.unobserve(el);
        setTimeout(function(){
          el.classList.add('hecha');
          el.classList.remove('lista');
        }, 2000);
      });
    }, {threshold:.14, rootMargin:'0px 0px -60px 0px'});

    elementos.forEach(function(el){ ioPreparar.observe(el); ioRevelar.observe(el); });
  }

  prepararYRevelar('.reveal', 'in');

  /* ==============================================================
     TITULARES DE SECCIÓN LETRA A LETRA

     El texto sale del CMS, así que se parte en el navegador. Si este
     bloque no llegara a correr, el <h2> se queda con su texto normal:
     la animación no puede esconder contenido que Google tiene que leer.
     ============================================================== */
  if (!reduce){
    document.querySelectorAll('.h-sec').forEach(function(h){
      var texto = h.textContent.trim();
      if (!texto) return;

      var frag = document.createDocumentFragment();
      var n = 0;
      texto.split(/(\s+)/).forEach(function(trozo){
        if (trozo === '') return;
        if (/^\s+$/.test(trozo)){ frag.appendChild(document.createTextNode(' ')); return; }
        var palabra = document.createElement('span');
        palabra.className = 'wd';
        trozo.split('').forEach(function(letra){
          var i = document.createElement('i');
          i.className = 'ltr';
          i.style.setProperty('--l', n++);
          i.textContent = letra;
          palabra.appendChild(i);
        });
        frag.appendChild(palabra);
      });
      h.textContent = '';
      h.appendChild(frag);
    });

    /* Los titulares son el caso peor: al encenderse arrancan de golpe unas 34
       transiciones, una por letra. Mismo truco de prepararlos antes. */
    prepararYRevelar('.h-sec', 'lit');
  }

  /* ==============================================================
     CONTADORES
     ============================================================== */
  function animarContador(el){
    var fin = parseInt(el.dataset.count, 10) || 0;
    var stat = el.closest('.stat');
    if (stat) stat.classList.add('on');

    if (reduce){
      el.textContent = fin;
      if (stat) stat.classList.add('done');
      return;
    }
    var t0 = null, dur = 1400;
    function paso(t){
      if (!t0) t0 = t;
      var p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(fin * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(paso);
      else if (stat) stat.classList.add('done');
    }
    requestAnimationFrame(paso);
  }
  var ioNum = new IntersectionObserver(function(entradas){
    entradas.forEach(function(en){
      if (en.isIntersecting){ animarContador(en.target); ioNum.unobserve(en.target); }
    });
  }, {threshold:.6});
  document.querySelectorAll('[data-count]').forEach(function(el){ ioNum.observe(el); });

  /* ==============================================================
     TARJETAS: brillo bajo el cursor + inclinación 3D
     ============================================================== */
  if (rico){
    document.querySelectorAll('.card').forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var r  = card.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width;
        var py = (e.clientY - r.top) / r.height;
        card.style.setProperty('--mx', (px * 100) + '%');
        card.style.setProperty('--my', (py * 100) + '%');
        card.style.setProperty('--ry', ((px - .5) *  7).toFixed(2) + 'deg');
        card.style.setProperty('--rx', ((py - .5) * -7).toFixed(2) + 'deg');
      });
      card.addEventListener('mouseleave', function(){
        card.style.removeProperty('--rx');
        card.style.removeProperty('--ry');
      });
    });

    /* Botones imantados: se acercan un poco al cursor antes de que lo pulses */
    document.querySelectorAll('.btn').forEach(function(btn){
      btn.addEventListener('mousemove', function(e){
        var r = btn.getBoundingClientRect();
        btn.style.setProperty('--tx', ((e.clientX - r.left - r.width  / 2) * .16).toFixed(1) + 'px');
        btn.style.setProperty('--ty', ((e.clientY - r.top  - r.height / 2) * .22 - 3).toFixed(1) + 'px');
      });
      btn.addEventListener('mouseleave', function(){
        btn.style.removeProperty('--tx');
        btn.style.removeProperty('--ty');
      });
    });

    /* Foco que sigue al cursor en la sección de contacto */
    var contacto = document.querySelector('.contact');
    if (contacto){
      contacto.addEventListener('mousemove', function(e){
        var r = contacto.getBoundingClientRect();
        contacto.style.setProperty('--cx', ((e.clientX - r.left) / r.width  * 100) + '%');
        contacto.style.setProperty('--cy', ((e.clientY - r.top)  / r.height * 100) + '%');
      }, {passive:true});
    }

    /* Paralaje suave del lente */
    var lens = document.getElementById('lens');
    if (lens){
      window.addEventListener('mousemove', function(e){
        var x = (e.clientX / window.innerWidth  - .5) * 18;
        var y = (e.clientY / window.innerHeight - .5) * 18;
        lens.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
      }, {passive:true});
    }
  }

  /* ==============================================================
     CHIPS EN CASCADA
     El índice lo pone el JS para no ensuciar la plantilla con un
     style="--c:N" en cada uno.
     ============================================================== */
  document.querySelectorAll('.chips').forEach(function(grupo){
    [].slice.call(grupo.children).forEach(function(chip, i){
      chip.style.setProperty('--c', i);
    });
  });
})();
</script>
