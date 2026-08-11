<style>
/* ================================================================
   INNPRO — Landing «Centro de Mando»
   Identidad heredada del panel: índigo #241D5E · azul #3488BD ·
   navy #020B3C · teal #5DCEBA · Chakra Petch + Sora.
   El motivo conductor es el lente de la cámara del logo: iris que
   se abre, barrido de radar, retículas de mira.

   DOS TEMAS. El oscuro es el «centro de mando» de siempre; el claro
   es el mismo lenguaje pero como PLANO TÉCNICO SOBRE PAPEL, que es
   lo que hace una empresa de ingeniería. No es el oscuro invertido:
   la rejilla se ve más, las sombras son de papel y el teal se
   oscurece para poder leerse (el #5DCEBA sobre blanco da 1.7:1 de
   contraste, o sea ilegible).

   El tema se elige con el atributo `data-tema` en <html>. Todo color
   pasa por un token: si mañana entra un tercer tema, se cambian los
   tokens y no se toca una sola regla de abajo.
   ================================================================ */

:root{
  /* ---- Marca: no cambian nunca, son la identidad ---- */
  --indigo:#241D5E;  --indigo-d:#191141;
  --blue:#3488BD;    --blue-d:#12669B;
  --navy:#020B3C;    --navy-d:#01071f;
  --teal:#5DCEBA;    --teal-d:#2AA995;

  --f-display:'Chakra Petch','Segoe UI',sans-serif;
  --f-body:'Sora','Segoe UI',system-ui,sans-serif;

  --shell:min(1240px,92vw);
  --ease:cubic-bezier(.22,1,.36,1);
  --ease-out:cubic-bezier(.16,1,.3,1);
}

/* ================================================================
   TEMA OSCURO (por defecto si nadie dice lo contrario)
   ================================================================ */
:root,
:root[data-tema="oscuro"]{
  color-scheme:dark;

  --bg:#020B3C;
  --ink:#eaf0ff;
  --ink-2:#d3ddf5;
  --muted:#9aa8c9;

  --accent:#5DCEBA;          /* acento decorativo */
  --accent-ink:#5DCEBA;      /* acento cuando es TEXTO y debe leerse */
  --brand-ink:#3488BD;

  --line:rgba(93,206,186,.18);
  --line-2:rgba(255,255,255,.07);
  --line-3:rgba(255,255,255,.06);

  --tint:rgba(93,206,186,.09);
  --tint-2:rgba(93,206,186,.18);
  --tint-blue:rgba(52,136,189,.11);
  --tint-blue-brd:rgba(52,136,189,.30);

  --card-bg:linear-gradient(160deg,rgba(36,29,94,.55),rgba(2,11,60,.55));
  --card-brd:rgba(255,255,255,.07);
  --card-brd-hov:rgba(93,206,186,.40);
  --card-glow:rgba(52,136,189,.22);

  /* Sin el desenfoque detrás, el fondo tiene que tapar más o el menú se
     pierde sobre el video. */
  --nav-bg:rgba(2,11,60,.90);
  --nav-bg-stuck:rgba(2,11,60,.97);
  --nav-ink:#c3d0ee;
  --nav-ink-hov:#fff;
  --drop-bg:rgba(2,11,60,.97);

  --top-bg:#191141;
  --top-ink:#b9c6e6;

  --foot-bg:#01071f;
  --foot-ink:#7b88a8;

  --shadow-1:0 26px 60px rgba(0,0,0,.45);
  --shadow-2:0 12px 40px rgba(0,0,0,.45);
  --shadow-3:0 18px 40px rgba(0,0,0,.45);
  --btn-shadow:rgba(52,136,189,.34);

  --grad-hero:
    radial-gradient(1200px 700px at 78% 28%,rgba(52,136,189,.28),transparent 62%),
    radial-gradient(900px 600px at 6% 84%,rgba(36,29,94,.75),transparent 60%),
    linear-gradient(165deg,#04123f 0%,#020B3C 46%,#010726 100%);
  --grad-alt:linear-gradient(180deg,#020B3C 0%,#060f36 50%,#020B3C 100%);
  --grad-contact:linear-gradient(140deg,#241D5E 0%,#0d1a55 55%,#020B3C 100%);
  --grad-contact-glow:radial-gradient(700px 420px at 82% 18%,rgba(93,206,186,.16),transparent 65%);
  --grad-art:
    radial-gradient(circle at 30% 25%,rgba(52,136,189,.35),transparent 58%),
    linear-gradient(150deg,#241D5E,#020B3C);
  --grad-title:linear-gradient(100deg,#5DCEBA 10%,#3488BD 60%,#fff 100%);

  --grid-line:rgba(93,206,186,.05);
  --grid-line-2:rgba(93,206,186,.07);
  --scan:rgba(93,206,186,.09);
  --grain-op:.16;

  --iris-ring:rgba(2,11,60,.85);
  --iris-core:#01071f;
  --stroke-marquee:rgba(178,197,238,.8);
  --stroke-num:rgba(93,206,186,.55);

  --band-bg:rgba(2,11,60,.42);
  --band-brd:rgba(93,206,186,.28);
  --guide-bg:rgba(255,255,255,.028);
  --guide-bg-hov:rgba(93,206,186,.07);

  --orb-1:rgba(52,136,189,.30);
  --orb-2:rgba(93,206,186,.18);
  --trace:rgba(93,206,186,.45);

  /* Velo sobre el video del hero. Va en diagonal y no plano: el texto vive a
     la izquierda y ahí tiene que tapar de verdad, mientras que a la derecha
     se abre para que se vea la sala de control. */
  --scrim:linear-gradient(100deg,
      rgba(2,11,60,.95) 0%, rgba(2,11,60,.87) 34%,
      rgba(2,11,60,.60) 68%, rgba(2,11,60,.44) 100%);
}

/* ================================================================
   TEMA CLARO — «plano técnico sobre papel»
   ================================================================ */
:root[data-tema="claro"]{
  color-scheme:light;

  --bg:#eff2f9;
  --ink:#0a1230;
  --ink-2:#1d2a52;
  --muted:#55638a;

  /* El teal de marca sigue mandando en lo decorativo, pero como texto
     se usa una versión oscurecida: la de marca no pasa contraste. */
  --accent:#2AA995;
  --accent-ink:#0b7565;
  --brand-ink:#1a5f8f;

  --line:rgba(36,29,94,.16);
  --line-2:rgba(36,29,94,.11);
  --line-3:rgba(36,29,94,.09);

  --tint:rgba(42,169,149,.10);
  --tint-2:rgba(42,169,149,.19);
  --tint-blue:rgba(52,136,189,.10);
  --tint-blue-brd:rgba(52,136,189,.32);

  --card-bg:linear-gradient(160deg,#ffffff,#f6f8fd);
  --card-brd:rgba(36,29,94,.10);
  --card-brd-hov:rgba(42,169,149,.55);
  --card-glow:rgba(52,136,189,.16);

  --nav-bg:rgba(255,255,255,.93);
  --nav-bg-stuck:rgba(255,255,255,.98);
  --nav-ink:#2b3763;
  --nav-ink-hov:#0a1230;
  --drop-bg:rgba(255,255,255,.98);

  --top-bg:#241D5E;          /* la barra superior se queda oscura: ancla la marca */
  --top-ink:#c8d2ee;

  --foot-bg:#e3e8f4;
  --foot-ink:#5b678a;

  --shadow-1:0 26px 60px rgba(15,26,66,.16);
  --shadow-2:0 12px 40px rgba(15,26,66,.12);
  --shadow-3:0 18px 40px rgba(15,26,66,.14);
  --btn-shadow:rgba(52,136,189,.32);

  --grad-hero:
    radial-gradient(1200px 700px at 78% 28%,rgba(52,136,189,.16),transparent 62%),
    radial-gradient(900px 600px at 6% 84%,rgba(93,206,186,.16),transparent 60%),
    linear-gradient(165deg,#ffffff 0%,#eef2fa 46%,#e6ebf7 100%);
  --grad-alt:linear-gradient(180deg,#eff2f9 0%,#e5eaf6 50%,#eff2f9 100%);
  --grad-contact:linear-gradient(140deg,#e8ecf8 0%,#eef2fb 55%,#e2e8f6 100%);
  --grad-contact-glow:radial-gradient(700px 420px at 82% 18%,rgba(42,169,149,.14),transparent 65%);
  --grad-art:
    radial-gradient(circle at 30% 25%,rgba(52,136,189,.20),transparent 58%),
    linear-gradient(150deg,#ffffff,#e9eef9);
  --grad-title:linear-gradient(100deg,#0b7565 10%,#1a5f8f 60%,#241D5E 100%);

  /* La rejilla se ve MÁS que en oscuro: es lo que vende el papel milimetrado. */
  --grid-line:rgba(36,29,94,.075);
  --grid-line-2:rgba(36,29,94,.10);
  --scan:rgba(52,136,189,.10);
  /* Cero: sobre papel el grano al 5% no se distinguía, y es una capa fija a
     pantalla completa por encima de TODO el documento que hay que mezclar en
     cada fotograma. Pagar eso por algo invisible no tiene sentido. En oscuro
     sí se nota y ahí se queda. */
  --grain-op:0;
  --grain-display:none;

  --iris-ring:rgba(255,255,255,.9);
  --iris-core:#eef2fa;
  /* Sobre papel el contorno tiene que ser más marcado que sobre navy: con
     .30 la cinta de capacidades se perdía del todo. */
  --stroke-marquee:rgba(36,29,94,.46);
  --stroke-num:rgba(42,169,149,.65);

  --band-bg:rgba(255,255,255,.72);
  --band-brd:rgba(42,169,149,.36);
  --guide-bg:rgba(255,255,255,.75);
  --guide-bg-hov:rgba(42,169,149,.10);

  --orb-1:rgba(52,136,189,.20);
  --orb-2:rgba(93,206,186,.20);
  --trace:rgba(42,169,149,.55);

  /* El video es una sala de control a oscuras y aquí el texto es navy sobre
     papel: hace falta mucho más velo que en el tema oscuro o no se lee nada.
     Queda como una filigrana detrás del papel, que era la idea. */
  --scrim:linear-gradient(100deg,
      rgba(239,242,249,.975) 0%, rgba(239,242,249,.94) 34%,
      rgba(239,242,249,.76) 68%, rgba(239,242,249,.64) 100%);
}

*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  margin:0;
  font-family:var(--f-body);
  color:var(--ink);
  background:var(--bg);
  overflow-x:hidden;
  -webkit-font-smoothing:antialiased;
  /* El cambio de tema se acompaña, no se corta en seco */
  transition:background-color .5s var(--ease),color .5s var(--ease);
}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}

/* Grano sutil sobre todo el documento: le quita el plástico al degradado.
   En claro va casi apagado —sobre papel el ruido fuerte se ve sucio. */
body::after{
  content:"";position:fixed;inset:0;z-index:9999;pointer-events:none;opacity:var(--grain-op);
  /* Con opacidad 0 el navegador se lo salta del todo en vez de seguir
     mezclando una textura a pantalla completa que no se ve. */
  display:var(--grain-display,block);
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E");
  transition:opacity .5s var(--ease);
}

/* ================================================================
   PANTALLA DE CARGA — la animación del logo
   ================================================================ */
.cargador{
  position:fixed;inset:0;z-index:9998;display:grid;place-items:center;gap:1.6rem;
  align-content:center;background:var(--bg);
  transition:opacity .6s var(--ease),visibility .6s;
  /* RED DE SEGURIDAD SIN JAVASCRIPT: si el script no llega a correr —error,
     bloqueador, navegador raro— esta animación la retira sola. El sitio no
     puede quedarse secuestrado detrás de un video pase lo que pase. */
  animation:cargadorRendirse .01s linear 6s forwards;
}
@keyframes cargadorRendirse{to{opacity:0;visibility:hidden}}
.cargador.fuera{opacity:0;visibility:hidden;pointer-events:none}
/* Ya se vio en esta visita: ni se pinta */
:root.sin-cargador .cargador{display:none}

.cargador__placa{
  width:min(520px,74vw);border-radius:14px;overflow:hidden;
  /* Exactamente el gris del fondo del video: sin esto se ve el borde del
     recuadro contra la placa mientras el video arranca. */
  background:#e0e0e0;
  box-shadow:0 30px 80px rgba(2,11,60,.30);
  opacity:0;transform:scale(.965);
  animation:placaEntra .55s var(--ease) .06s forwards;
}
@keyframes placaEntra{to{opacity:1;transform:none}}
.cargador__video{width:100%;height:auto;display:block}

.cargador__barra{
  width:min(520px,74vw);height:2px;background:var(--line-2);overflow:hidden;
}
.cargador__barra i{
  display:block;height:100%;width:38%;
  background:linear-gradient(90deg,transparent,var(--accent),transparent);
  animation:lanzadera 1.5s var(--ease) infinite;
}
@keyframes lanzadera{
  0%{transform:translateX(-110%)}
  100%{transform:translateX(370%)}
}

.cargador__saltar{
  position:absolute;bottom:26px;right:26px;background:0;border:0;cursor:pointer;
  font-family:var(--f-display);font-size:.72rem;letter-spacing:.22em;
  text-transform:uppercase;color:var(--muted);padding:.5rem .8rem;
  opacity:0;animation:fadeUp .5s var(--ease) 1.2s forwards;transition:color .3s;
}
.cargador__saltar:hover{color:var(--accent-ink)}

/* La entrada del hero espera a que la carga se retire, EN PAUSA.
   Se probó con retardos en vez de pausa y no sirve: el reloj de la animación
   corre desde que cargó la página, así que al soltarla la intro ya habría
   "pasado" y el hero aparecería de golpe, sin iris que se abra ni titular que
   suba —justo lo que se quería proteger—.

   Con pausa se reanuda desde el principio. El riesgo de dejar algo pausado
   para siempre está cubierto: quien añade la clase `cargando` es un script,
   así que sin JavaScript no se añade nunca y el hero anima como toda la vida.
   Y si el script de abajo se rompiera, hay un temporizador que la suelta. */
:root.cargando .hero__title .w i,
:root.cargando .hero__sub,
:root.cargando .hero__cta,
:root.cargando .hero__stats,
:root.cargando .lens__iris,
:root.cargando .lens__cross,
:root.cargando .lens__glint,
:root.cargando .circuit path,
:root.cargando .circuit circle{animation-play-state:paused}

::selection{background:var(--accent);color:#fff}
::-webkit-scrollbar{width:11px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:linear-gradient(var(--blue),var(--indigo));border-radius:9px}

/* ---------- utilidades de composición ---------- */
.shell{width:var(--shell);margin-inline:auto}
.eyebrow{
  font-family:var(--f-display);font-size:.74rem;font-weight:600;
  letter-spacing:.34em;text-transform:uppercase;color:var(--accent-ink);
  display:flex;align-items:center;gap:.7rem;
}
.eyebrow::before{
  content:"";width:34px;height:1px;background:linear-gradient(90deg,transparent,var(--accent));
  transform-origin:right;transform:scaleX(0);transition:transform .8s var(--ease) .1s;
}
.reveal.in .eyebrow::before,.hero .eyebrow::before{transform:scaleX(1)}

.h-sec{
  font-family:var(--f-display);font-weight:600;line-height:1.05;
  font-size:clamp(2rem,4.4vw,3.4rem);margin:.7rem 0 0;letter-spacing:-.01em;
}
/* Las letras las parte el JS. Si el JS no corre, no existe ningún .ltr
   y el titular se ve tal cual: la animación nunca puede esconder texto. */
.h-sec .wd{display:inline-block;white-space:nowrap}
.h-sec .ltr{
  display:inline-block;font-style:normal;opacity:0;
  transform:translateY(.42em) rotate(4deg);
  transition:opacity .5s var(--ease-out),transform .6s var(--ease-out);
  transition-delay:calc(var(--l,0) * 22ms);
}
.h-sec.lit .ltr{opacity:1;transform:none}

.lead{color:var(--muted);font-weight:300;line-height:1.75;font-size:1.03rem}

/* Corchetes de mira en las esquinas — el tic visual de la marca */
.bracket{position:relative}
.bracket::before,.bracket::after{
  content:"";position:absolute;width:16px;height:16px;pointer-events:none;
  border:1.5px solid var(--accent);opacity:.55;transition:all .45s var(--ease);
}
.bracket::before{top:-1px;left:-1px;border-right:0;border-bottom:0}
.bracket::after{bottom:-1px;right:-1px;border-left:0;border-top:0}
.bracket:hover::before,.bracket:hover::after{width:26px;height:26px;opacity:1}

/* Botones */
.btn{
  --bg-btn:var(--blue);
  display:inline-flex;align-items:center;gap:.6rem;
  font-family:var(--f-display);font-weight:600;font-size:.86rem;
  letter-spacing:.12em;text-transform:uppercase;
  padding:.95rem 1.6rem;border:0;cursor:pointer;position:relative;overflow:hidden;
  clip-path:polygon(11px 0,100% 0,100% calc(100% - 11px),calc(100% - 11px) 100%,0 100%,0 11px);
  background:var(--bg-btn);color:#fff;
  /* --mx/--my los mueve el JS: el botón se imanta hacia el cursor */
  transform:translate3d(var(--tx,0),var(--ty,0),0);
  transition:transform .35s var(--ease),box-shadow .35s var(--ease);
}
.btn span{position:relative;z-index:1}
.btn::before{
  content:"";position:absolute;inset:0;z-index:0;
  background:linear-gradient(120deg,var(--indigo),var(--teal-d));
  transform:translateX(-101%);transition:transform .45s var(--ease);
}
/* Destello que cruza el botón al pasar el cursor */
.btn::after{
  content:"";position:absolute;top:0;bottom:0;width:38%;z-index:1;pointer-events:none;
  background:linear-gradient(100deg,transparent,rgba(255,255,255,.30),transparent);
  transform:translateX(-260%) skewX(-18deg);
}
/* El -3px vive en una variable para que el imán del cursor (que escribe
   --tx/--ty en línea) lo sustituya en vez de pelearse con él. Sin JS o sin
   ratón, el botón sigue levantándose igual. */
.btn:hover{--ty:-3px;box-shadow:0 14px 34px var(--btn-shadow)}
.btn:hover::before{transform:translateX(0)}
.btn:hover::after{transform:translateX(360%) skewX(-18deg);transition:transform .75s var(--ease)}
.btn:active{transform:translate3d(var(--tx,0),var(--ty,0),0) scale(.97)}
.btn--ghost{background:transparent;box-shadow:inset 0 0 0 1.5px var(--tint-2);color:var(--accent-ink)}
.btn--ghost:hover{color:#fff;box-shadow:inset 0 0 0 1.5px transparent}

/* ================================================================
   BARRA DE PROGRESO DE LECTURA
   Dice cuánto falta. En una portada larga es la diferencia entre
   «esto no se acaba nunca» y seguir bajando.
   ================================================================ */
.prog{
  position:fixed;top:0;left:0;right:0;height:2px;z-index:70;pointer-events:none;
  background:linear-gradient(90deg,var(--accent),var(--blue),var(--indigo));
  /* El JS escribe el transform en cada marco, así que no lleva transition:
     una transición encima de un valor que ya cambia 60 veces por segundo solo
     añade retraso. */
  transform:scaleX(0);transform-origin:0 50%;
}

/* ================================================================
   BARRA SUPERIOR + NAV
   ================================================================ */
.topbar{
  background:var(--top-bg);font-size:.76rem;color:var(--top-ink);
  border-bottom:1px solid var(--line-3);position:relative;z-index:60;
  transition:background-color .5s var(--ease),color .5s var(--ease);
}
.topbar .shell{display:flex;gap:1.4rem;align-items:center;justify-content:flex-end;
  min-height:38px;flex-wrap:wrap}
.topbar b{color:var(--teal);font-weight:600}
.topbar .soc{display:flex;gap:.85rem;margin-left:.4rem}
.topbar .soc a{opacity:.65;transition:opacity .25s,transform .25s}
.topbar .soc a:hover{opacity:1;transform:translateY(-2px)}

/* Sin backdrop-filter. Llevaba blur(16px) saturate(150%) y es la barra
   pegajosa que está SIEMPRE en pantalla: obliga a releer y desenfocar lo que
   tiene detrás en cada fotograma del scroll, y encima de un video en marcha
   es de lo más caro del documento. Con el fondo casi opaco se lee igual de
   bien y no cuesta nada. */
.nav{
  position:sticky;top:0;z-index:50;
  background:var(--nav-bg);
  border-bottom:1px solid var(--line-2);
  transition:background-color .4s var(--ease),box-shadow .4s var(--ease);
}
.nav.is-stuck{background:var(--nav-bg-stuck);box-shadow:var(--shadow-2)}
.nav .shell{display:flex;align-items:center;gap:2rem;min-height:78px}
.nav__logo img{height:44px;width:auto;transition:transform .5s var(--ease)}
.nav__logo:hover img{transform:scale(1.05) rotate(-2deg)}
.nav__links{display:flex;gap:1.9rem;margin-left:auto;align-items:center}
.nav__links a{
  font-family:var(--f-display);font-size:.82rem;font-weight:500;
  letter-spacing:.14em;text-transform:uppercase;color:var(--nav-ink);
  position:relative;padding:.4rem 0;transition:color .3s;
}
.nav__links a::after{
  content:"";position:absolute;left:0;bottom:0;height:2px;width:0;
  background:var(--accent);transition:width .35s var(--ease);
}
.nav__links a:hover{color:var(--nav-ink-hov)}
.nav__links a:hover::after{width:100%}
/* La sección en la que estás se marca sola mientras haces scroll */
.nav__links a.is-here{color:var(--nav-ink-hov)}
.nav__links a.is-here::after{width:100%;background:var(--accent);opacity:.55}
.nav .btn{padding:.72rem 1.25rem;font-size:.78rem}
.nav .btn::after{display:none}
.nav__burger{display:none;margin-left:auto;background:0;border:0;cursor:pointer;padding:.4rem}
.nav__burger span{display:block;width:26px;height:2px;background:var(--accent);margin:5px 0;transition:.3s var(--ease)}
.nav__burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.nav__burger.open span:nth-child(2){opacity:0}
.nav__burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* ---------- Interruptor de tema: es un iris de cámara ----------
   No es un sol y una luna genéricos. Es el mismo lente del logo:
   abierto deja pasar la luz (claro), cerrado la bloquea (oscuro). */
.tema{
  width:34px;height:34px;flex:0 0 34px;border-radius:50%;cursor:pointer;padding:0;
  background:var(--tint);border:1px solid var(--line);
  display:grid;place-items:center;color:var(--accent-ink);
  transition:transform .45s var(--ease),background .35s,border-color .35s;
}
.tema:hover{transform:rotate(35deg) scale(1.08);background:var(--tint-2)}
.tema svg{width:17px;height:17px;display:block}
.tema .tema__iris{transform-origin:center;transition:transform .5s var(--ease)}
:root[data-tema="claro"] .tema .tema__iris{transform:scale(.42)}
.tema .tema__hoja{transform-origin:center;transition:opacity .4s var(--ease),transform .5s var(--ease)}
:root[data-tema="oscuro"] .tema .tema__hoja{opacity:0;transform:rotate(-40deg) scale(.6)}
.topbar .tema{width:26px;height:26px;flex-basis:26px;margin-left:.5rem}
.topbar .tema svg{width:14px;height:14px}
/* Solo uno de los dos a la vez: en escritorio el del menú, en móvil el de
   la barra superior —allí el menú vive detrás de la hamburguesa. */
.tema--top{display:none}
@media (max-width:980px){
  .tema--nav{display:none}
  .tema--top{display:grid}
}

/* ================================================================
   HERO — la pieza: iris de cámara que se abre
   ================================================================ */
.hero{
  position:relative;overflow:hidden;
  /* El degradado se queda como respaldo: si el video no carga, no se
     descarga o el navegador lo bloquea, el hero sigue siendo el de antes. */
  background:var(--grad-hero);
  transition:background .5s var(--ease);
}

/* ---- Video de fondo: la sala de control ----
   El orden de capas del hero, de abajo arriba:
     0  video
     1  velo (scrim) y orbes
     2  rejilla, línea de escaneo y trazado de circuito
     3  contenido
     4  indicador de scroll
   Sin z-index explícitos el video taparía la rejilla, que es la que ata el
   fondo al resto del sitio. */
.hero__video{
  position:absolute;inset:0;z-index:0;width:100%;height:100%;
  object-fit:cover;pointer-events:none;
  opacity:0;transition:opacity 1.1s var(--ease);
}
.hero__video.puesto{opacity:1}
/* En claro había un filter:blur(3px) sobre el video para disimular el
   logotipo del monitor central, que con el velo de papel se quedaba flotando
   suelto. Desenfocar a pantalla completa un video que corre a 24 fps es de
   las cosas más caras que se le pueden pedir a la GPU, así que se resuelve
   moviendo el encuadre: se amplía y se desplaza para que ese monitor caiga
   detrás del lente. Un transform lo resuelve el compositor y cuesta cero. */
:root[data-tema="claro"] .hero__video{transform:scale(1.16) translateX(6%)}
.hero__scrim{position:absolute;inset:0;z-index:1;pointer-events:none;background:var(--scrim);
  transition:background .5s var(--ease)}

/* Con video de fondo los orbes sobran un poco: se bajan para no emborronarlo */
.hero.con-video .orb{opacity:.45}

/* rejilla blueprint */
.hero::before{
  content:"";position:absolute;inset:0;z-index:2;pointer-events:none;
  background-image:
    linear-gradient(var(--grid-line) 1px,transparent 1px),
    linear-gradient(90deg,var(--grid-line) 1px,transparent 1px);
  background-size:54px 54px;
  mask-image:radial-gradient(circle at 60% 45%,#000 10%,transparent 78%);
  -webkit-mask-image:radial-gradient(circle at 60% 45%,#000 10%,transparent 78%);
}
/* línea de escaneo que recorre el hero */
.hero::after{
  content:"";position:absolute;left:0;right:0;z-index:2;height:140px;pointer-events:none;
  background:linear-gradient(180deg,transparent,var(--scan),transparent);
  animation:scan 7.5s linear infinite;
}
@keyframes scan{
  0%{transform:translateY(-160px)}
  100%{transform:translateY(105vh)}
}

/* Orbes que respiran despacio al fondo. Dan profundidad sin pedir
   una sola imagen: todo es degradado, pesa cero. */
/* El difuminado lo da el propio degradado radial, NO un filter.
   Antes esto era un color plano con filter:blur(60px) sobre un círculo de
   620px, y además la animación tocaba `scale`: cada fotograma obligaba a
   rerasterizar un desenfoque enorme. Se ve idéntico y ahora es un degradado
   estático que solo se desplaza —trabajo del compositor, no de la CPU—. */
.orb{
  position:absolute;z-index:1;border-radius:50%;pointer-events:none;
  animation:drift 26s ease-in-out infinite;transition:opacity .6s var(--ease);
}
.orb--1{width:46vw;height:46vw;max-width:620px;max-height:620px;top:-12%;right:-8%;
  background:radial-gradient(circle,var(--orb-1) 0%,transparent 68%)}
.orb--2{width:38vw;height:38vw;max-width:500px;max-height:500px;bottom:-16%;left:-10%;
  background:radial-gradient(circle,var(--orb-2) 0%,transparent 68%);
  animation-duration:34s;animation-direction:reverse}
/* Solo traslación: en cuanto entra un scale, el desenfoque del degradado hay
   que volver a pintarlo. */
@keyframes drift{
  0%,100%{transform:translate3d(0,0,0)}
  33%{transform:translate3d(4%,-5%,0)}
  66%{transform:translate3d(-5%,4%,0)}
}

/* Trazado de circuito: pistas que se dibujan solas y por las que
   viaja un pulso. Es literalmente lo que vende Innpro. */
.circuit{position:absolute;inset:0;z-index:2;width:100%;height:100%;pointer-events:none;opacity:.85}
.circuit path{
  fill:none;stroke:var(--trace);stroke-width:1.1;
  stroke-dasharray:var(--len,400);stroke-dashoffset:var(--len,400);
  animation:trazar 2.6s var(--ease) forwards;
  animation-delay:calc(var(--t,0) * 1s + .4s);
}
@keyframes trazar{to{stroke-dashoffset:0}}
/* El pulso viaja con <animateMotion> y no con offset-path de CSS: la ruta ya
   está escrita en el <path>, así que se reutiliza en vez de repetir cada
   coordenada dentro del CSS. Lo único que hace CSS es encenderlo. */
.circuit circle{
  fill:var(--accent);opacity:0;filter:drop-shadow(0 0 5px var(--accent));
  animation:encender .9s var(--ease) forwards;
  animation-delay:calc(var(--t,0) * 1s + 2.2s);
}
@keyframes encender{to{opacity:.95}}

.hero .shell{
  position:relative;z-index:3;
  display:grid;grid-template-columns:1.08fr .92fr;gap:3.2rem;align-items:center;
  min-height:calc(100vh - 116px);padding:3.4rem 0 4.6rem;
}

.hero__title{
  font-family:var(--f-display);font-weight:700;
  font-size:clamp(2.4rem,5.3vw,4.1rem);line-height:1;letter-spacing:-.02em;
  margin:1.2rem 0 0;text-transform:uppercase;
}
.hero__title .w{display:inline-block;overflow:hidden;vertical-align:top}
.hero__title .w i{
  display:inline-block;font-style:normal;
  transform:translateY(105%);animation:rise .9s var(--ease) forwards;
  animation-delay:calc(var(--i) * 80ms + 250ms);
}
@keyframes rise{to{transform:translateY(0)}}
/* El degradado del titular era animado (background-position, en bucle
   infinito). `background-position` no lo puede resolver el compositor: obliga
   a repintar en cada fotograma un texto enorme con background-clip, para
   siempre, aunque nadie esté mirando. El degradado estático se ve casi igual
   y no cuesta nada. */
.hero__title em{
  font-style:normal;
  background:var(--grad-title);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}

.hero__sub{
  margin:1.3rem 0 0;max-width:33rem;font-size:1.06rem;font-weight:300;
  line-height:1.65;color:var(--muted);
  opacity:0;animation:fadeUp .9s var(--ease) .95s forwards;
}
.hero__cta{display:flex;gap:1rem;margin-top:2rem;flex-wrap:wrap;
  opacity:0;animation:fadeUp .9s var(--ease) 1.15s forwards}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}

/* Las tres cifras van en UNA sola fila: si envuelven, la tercera se cae
   por debajo del pliegue en pantallas de 900px de alto. */
.hero__stats{
  display:flex;gap:clamp(1.2rem,3vw,2.4rem);margin-top:2.6rem;flex-wrap:nowrap;
  opacity:0;animation:fadeUp .9s var(--ease) 1.35s forwards;
}
.stat{position:relative;padding-left:.9rem}
.stat::before{
  content:"";position:absolute;left:0;top:.15em;bottom:.9em;width:2px;
  background:linear-gradient(var(--accent),transparent);
  transform:scaleY(0);transform-origin:top;transition:transform .7s var(--ease)
}
.stat.on::before{transform:scaleY(1)}
.stat b{
  display:block;font-family:var(--f-display);font-weight:700;
  font-size:clamp(1.7rem,2.4vw,2.3rem);line-height:1;color:var(--ink);
  font-variant-numeric:tabular-nums;
}
.stat b::after{content:attr(data-suf);color:var(--accent-ink);font-size:1.3rem;vertical-align:super}
.stat span{
  display:block;margin-top:.45rem;font-size:clamp(.62rem,.9vw,.72rem);letter-spacing:.16em;
  text-transform:uppercase;color:var(--muted);line-height:1.5;
}
/* Chispazo cuando el contador llega a su número */
.stat.done b{animation:chispa .7s var(--ease)}
@keyframes chispa{
  0%{text-shadow:none}
  40%{text-shadow:0 0 22px var(--accent)}
  100%{text-shadow:none}
}

/* ---- el iris ---- */
.lens{position:relative;aspect-ratio:1;width:100%;max-width:520px;margin-inline:auto;
  display:grid;place-items:center}
.lens__ring{
  position:absolute;border-radius:50%;border:1px solid var(--tint-2);
}
.lens__ring:nth-child(1){inset:0;animation:spin 34s linear infinite}
.lens__ring:nth-child(2){inset:9%;border-style:dashed;border-color:var(--tint-blue-brd);
  animation:spin 22s linear infinite reverse}
.lens__ring:nth-child(3){inset:20%;border-color:var(--tint-2)}
@keyframes spin{to{transform:rotate(360deg)}}

/* Marcas de grado alrededor del lente: detalle de instrumento */
.lens__ticks{position:absolute;inset:-2%;border-radius:50%;
  background:repeating-conic-gradient(var(--tint-2) 0deg 1deg,transparent 1deg 15deg);
  mask-image:radial-gradient(circle,transparent 47%,#000 48%,#000 50%,transparent 51%);
  -webkit-mask-image:radial-gradient(circle,transparent 47%,#000 48%,#000 50%,transparent 51%);
  /* Giraban a 90s por vuelta: imperceptible, y un degradado cónico con máscara
     girando hay que repintarlo. Las marcas son simétricas, no se nota. */
}

/* barrido de radar */
.lens__sweep{
  position:absolute;inset:9%;border-radius:50%;
  background:conic-gradient(from 0deg,transparent 0deg,var(--tint-2) 34deg,transparent 62deg);
  animation:spin 4.2s linear infinite;
  mask-image:radial-gradient(circle,transparent 30%,#000 31%);
  -webkit-mask-image:radial-gradient(circle,transparent 30%,#000 31%);
}
/* pupila: se abre al cargar */
.lens__iris{
  position:relative;width:38%;aspect-ratio:1;border-radius:50%;
  background:radial-gradient(circle at 35% 30%,#8fe6d6,var(--teal) 26%,var(--blue) 58%,var(--indigo) 100%);
  box-shadow:0 0 0 10px var(--iris-ring),0 0 80px var(--tint-2),
             inset 0 0 40px rgba(2,11,60,.6);
  transform:scale(0);animation:iris 1.5s var(--ease) .35s forwards;
}
@keyframes iris{0%{transform:scale(0) rotate(-90deg)}100%{transform:scale(1) rotate(0)}}
.lens__iris::after{
  content:"";position:absolute;inset:26%;border-radius:50%;background:var(--iris-core);
  box-shadow:inset 0 0 22px var(--tint-2);
}
/* La pupila late despacio, como un obturador respirando */
.lens__iris::before{
  content:"";position:absolute;inset:-14%;border-radius:50%;
  border:1px solid var(--tint-2);animation:ping 4.2s ease-out infinite;
}
/* destello del lente */
.lens__glint{
  position:absolute;width:16%;aspect-ratio:1;border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.9),transparent 68%);
  top:26%;left:30%;filter:blur(2px);opacity:0;
  animation:glint 1s var(--ease) 1.5s forwards;
}
@keyframes glint{to{opacity:.75}}
/* cruceta */
.lens__cross{position:absolute;inset:0;opacity:0;animation:fadeUp .8s var(--ease) 1.3s forwards}
.lens__cross i{position:absolute;background:var(--tint-2)}
.lens__cross i:nth-child(1){left:50%;top:0;bottom:0;width:1px}
.lens__cross i:nth-child(2){top:50%;left:0;right:0;height:1px}
/* nodos que orbitan */
.node{
  position:absolute;width:9px;height:9px;border-radius:50%;background:var(--accent);
  box-shadow:0 0 14px var(--accent);
  transform-origin:center;
}
.node::after{
  content:"";position:absolute;inset:-7px;border-radius:50%;
  border:1px solid var(--tint-2);animation:ping 2.6s ease-out infinite;
}
@keyframes ping{0%{transform:scale(.5);opacity:1}100%{transform:scale(1.9);opacity:0}}
.node:nth-of-type(1){top:6%;left:48%;animation:orbit 17s linear infinite}
.node:nth-of-type(2){top:47%;left:2%;animation:orbit 23s linear infinite reverse;background:var(--blue);box-shadow:0 0 14px var(--blue)}
.node:nth-of-type(3){bottom:9%;right:14%;animation:orbit 20s linear infinite}
@keyframes orbit{to{transform:rotate(360deg) translateX(9px) rotate(-360deg)}}

.hero__scroll{
  position:absolute;bottom:26px;left:50%;transform:translateX(-50%);z-index:4;
  font-family:var(--f-display);font-size:.68rem;letter-spacing:.3em;text-transform:uppercase;
  color:var(--muted);display:grid;justify-items:center;gap:.6rem;
}
.hero__scroll i{display:block;width:1px;height:42px;background:linear-gradient(var(--accent),transparent);
  animation:drop 2s ease-in-out infinite}
@keyframes drop{0%,100%{transform:scaleY(.35);transform-origin:top}50%{transform:scaleY(1);transform-origin:top}}

/* ================================================================
   NADA SE ANIMA FUERA DE PANTALLA

   El navegador no para las animaciones de lo que no se ve: el radar del
   lente, los orbes, la línea de escaneo, la cinta y los pulsos seguían
   girando y recomponiéndose mientras leías el pie de página. En una pantalla
   de 60 Hz eso pasa desapercibido; en una de 179 Hz, donde cada fotograma
   dura 5,6 ms, se lo come todo.
   ================================================================ */
.quieta,
.quieta *,
.quieta *::before,
.quieta *::after{animation-play-state:paused !important}

/* ================================================================
   REVELADO AL HACER SCROLL
   Cuatro direcciones en vez de una: que todo suba igual se nota y
   aburre. Cada sección entra por donde tiene sentido.
   ================================================================ */
/* `lista` = está a punto de entrar en pantalla. Se le avisa al navegador con
   antelación para que prepare la capa ANTES, y no justo en el fotograma en
   que empieza a moverse: ese trabajo hecho a destiempo es lo que se veía como
   un tropiezo cada vez que aparecía un bloque. Se retira con `hecha` en
   cuanto termina, porque un will-change permanente cuesta memoria de GPU. */
.reveal.lista,.h-sec.lista .ltr{will-change:opacity,transform}
.reveal.hecha,.h-sec.hecha .ltr{will-change:auto}

.reveal{opacity:0;transform:translateY(38px);transition:opacity .85s var(--ease),transform .85s var(--ease);
  transition-delay:var(--d,0ms)}
.reveal--left{transform:translateX(-46px)}
.reveal--right{transform:translateX(46px)}
.reveal--zoom{transform:scale(.93)}
.reveal.in{opacity:1;transform:none}

/* ================================================================
   SERVICIOS
   ================================================================ */
section{position:relative}
.sect{padding:6rem 0}
.sect--alt{background:var(--grad-alt);transition:background .5s var(--ease)}

/* Regla que cose las secciones: se dibuja de izquierda a derecha al entrar.
   Empezó siendo una línea vertical al 50% y era un error: cortaba las
   tarjetas por la mitad y se leía como un borde puesto por accidente.
   Después se ataba al scroll fotograma a fotograma, y eso costaba media
   página de recálculo de estilos por marco —ver el comentario del JS—. */
.sect--alt::before{
  content:"";position:absolute;left:0;top:0;width:100%;height:1px;
  background:linear-gradient(90deg,transparent,var(--accent),transparent);opacity:.55;
  transform:scaleX(0);transform-origin:left;
  transition:transform 1.2s var(--ease);pointer-events:none;
}
.sect--alt.cosida::before{transform:scaleX(1)}

.svc{display:grid;grid-template-columns:repeat(3,1fr);gap:1.6rem;margin-top:3.4rem}
.card{
  /* En columna para que el «Ver más» se pegue abajo con margin-top:auto. Sin
     esto, en cuanto una tarjeta tiene más viñetas que otra, los enlaces se
     quedan a alturas distintas y la fila se ve descuadrada. */
  display:flex;flex-direction:column;
  position:relative;padding:2.6rem 2rem;overflow:hidden;
  background:var(--card-bg);
  border:1px solid var(--card-brd);
  /* --rx/--ry las mueve el JS: la tarjeta se inclina hacia el cursor */
  transform:perspective(900px) rotateX(var(--rx,0deg)) rotateY(var(--ry,0deg)) translateY(var(--lift,0));
  transition:transform .5s var(--ease),border-color .5s,box-shadow .5s,background .5s;
  /* Sin `will-change`: mantenía las tres tarjetas promocionadas a capa propia
     durante toda la vida de la página, gastando memoria de GPU para una
     animación que solo ocurre al pasar el cursor por encima. */
}
/* El brillo va en su PROPIO elemento, no en .card::before.
   Las tarjetas llevan también la clase `bracket`, y sus dos pseudoelementos
   ya están ocupados por los corchetes de mira —el tic visual de la marca—.
   Mientras el brillo compartió ::before con el corchete, ganaba el `inset:0`
   del brillo pero sobrevivían el `width:16px` y el borde del corchete: o sea
   que el «brillo que sigue al cursor» era en realidad un cuadradito de 16px
   pegado a la esquina. Sobre navy no se veía; sobre papel canta. */
.card__glow{
  position:absolute;inset:0;opacity:0;transition:opacity .5s;pointer-events:none;
  background:radial-gradient(420px 260px at var(--mx,50%) var(--my,0%),var(--card-glow),transparent 70%);
}
.card:hover{--lift:-10px;border-color:var(--card-brd-hov);box-shadow:var(--shadow-1)}
.card:hover .card__glow{opacity:1}
.card__n{
  font-family:var(--f-display);font-size:.72rem;letter-spacing:.28em;color:var(--accent-ink);
  position:relative;z-index:1;
}
.card__ico{
  width:56px;height:56px;margin:1.4rem 0 1.5rem;position:relative;z-index:1;
  display:grid;place-items:center;color:var(--accent-ink);
  background:var(--tint);border:1px solid var(--tint-2);
  clip-path:polygon(12px 0,100% 0,100% calc(100% - 12px),calc(100% - 12px) 100%,0 100%,0 12px);
  transition:transform .5s var(--ease),background .4s;
}
.card:hover .card__ico{transform:rotate(-8deg) scale(1.08);background:var(--tint-2)}
.card h3{
  font-family:var(--f-display);font-weight:600;font-size:1.32rem;line-height:1.25;
  margin:0 0 .9rem;position:relative;z-index:1;
}
.card p{margin:0;color:var(--muted);font-weight:300;line-height:1.7;font-size:.95rem;
  position:relative;z-index:1}
/* «Qué incluye»: la lista con visto dentro de la tarjeta. El visto se dibuja
   con un pseudoelemento y no con un carácter para poder darle color y grosor
   propios sin depender de la tipografía. */
.card__puntos{
  list-style:none;margin:1.3rem 0 0;padding:0;position:relative;z-index:1;
  border-top:1px solid var(--line-2);padding-top:1.1rem;
}
.card__puntos li{
  position:relative;padding-left:1.55rem;margin:.55rem 0;
  font-size:.89rem;line-height:1.5;font-weight:300;color:var(--ink-2);
}
.card__puntos li::before{
  content:"";position:absolute;left:0;top:.34em;width:9px;height:5px;
  border-left:1.6px solid var(--accent-ink);border-bottom:1.6px solid var(--accent-ink);
  transform:rotate(-45deg);
}

/* Insignia de esquina: «Más solicitado» y parecidos. */
.card__sello{
  position:absolute;top:0;right:0;z-index:2;
  font-family:var(--f-display);font-size:.66rem;font-weight:600;
  letter-spacing:.16em;text-transform:uppercase;
  padding:.42rem .8rem;color:#fff;background:var(--blue);
  clip-path:polygon(10px 0,100% 0,100% 100%,0 100%);
}

.card__more{
  display:inline-flex;align-self:flex-start;align-items:center;gap:.5rem;
  margin-top:auto;padding-top:1.6rem;position:relative;z-index:1;
  font-family:var(--f-display);font-size:.78rem;letter-spacing:.16em;text-transform:uppercase;
  color:var(--accent-ink);transition:gap .3s var(--ease);
}
/* Sin enlace no es un enlace: que no parezca pulsable. */
span.card__more{opacity:.45}
.card:hover .card__more{gap:.95rem}

/* ================================================================
   EMPRESA — dos columnas con marco técnico
   ================================================================ */
.about{display:grid;grid-template-columns:.95fr 1.05fr;gap:4rem;align-items:center}
.about__art{
  position:relative;aspect-ratio:4/3.4;
  background:var(--grad-art);
  border:1px solid var(--tint-2);
  display:grid;place-items:center;overflow:hidden;
  transition:background .5s var(--ease);
}
.about__art::before{
  content:"";position:absolute;inset:0;
  background-image:
    linear-gradient(var(--grid-line-2) 1px,transparent 1px),
    linear-gradient(90deg,var(--grid-line-2) 1px,transparent 1px);
  background-size:26px 26px;
}
/* Esquinas de plano técnico */
.about__art::after{
  content:"";position:absolute;inset:14px;pointer-events:none;
  background:
    linear-gradient(var(--accent),var(--accent)) 0 0/22px 1px no-repeat,
    linear-gradient(var(--accent),var(--accent)) 0 0/1px 22px no-repeat,
    linear-gradient(var(--accent),var(--accent)) 100% 100%/22px 1px no-repeat,
    linear-gradient(var(--accent),var(--accent)) 100% 100%/1px 22px no-repeat;
  opacity:.6;
}
.about__art img{position:relative;width:66%;filter:drop-shadow(0 18px 40px rgba(0,0,0,.35));
  animation:flota 7s ease-in-out infinite}
@keyframes flota{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.about__art .pulse{
  position:absolute;width:58%;aspect-ratio:1;border-radius:50%;
  border:1px solid var(--tint-2);animation:ping 3.4s ease-out infinite;
}
.about__art .pulse:nth-of-type(2){animation-delay:1.7s}

/* ================================================================
   LINEAMIENTOS ESTRATÉGICOS
   ================================================================ */
.guide{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.3rem;margin-top:3.4rem}
.guide__i{
  padding:1.9rem 1.5rem;position:relative;overflow:hidden;
  background:var(--guide-bg);border-left:2px solid var(--blue);
  transition:background .45s,border-color .45s,transform .45s var(--ease),box-shadow .45s;
}
/* El riel de la izquierda se llena cuando la tarjeta entra en pantalla */
.guide__i::before{
  content:"";position:absolute;left:-2px;top:0;width:2px;height:100%;
  background:var(--accent);transform:scaleY(0);transform-origin:top;
  transition:transform .8s var(--ease);transition-delay:var(--d,0ms);
}
.guide__i.in::before{transform:scaleY(1)}
.guide__i:hover{background:var(--guide-bg-hov);border-left-color:var(--accent);
  transform:translateX(6px);box-shadow:var(--shadow-2)}
.guide__i b{
  font-family:var(--f-display);font-size:2.3rem;font-weight:700;line-height:1;
  color:transparent;-webkit-text-stroke:1px var(--stroke-num);display:block;margin-bottom:.9rem;
  transition:-webkit-text-stroke-color .4s;
}
.guide__i:hover b{-webkit-text-stroke-color:var(--accent)}
.guide__i p{margin:0;font-size:.95rem;line-height:1.6;font-weight:300;color:var(--ink-2)}

/* ================================================================
   EXPERIENCIA — capacidades en cinta + texto
   ================================================================ */
.exp{display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start}
.chips{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:2rem}
.chip{
  font-family:var(--f-display);font-size:.78rem;letter-spacing:.06em;
  padding:.6rem 1rem;color:var(--ink-2);
  background:var(--tint-blue);border:1px solid var(--tint-blue-brd);
  transition:all .4s var(--ease);
}
.chip:hover{background:var(--blue);color:#fff;transform:translateY(-3px)}
/* Entran en cascada cuando el bloque se revela */
.reveal .chip{opacity:0;transform:translateY(12px);
  transition:opacity .5s var(--ease),transform .5s var(--ease),background .4s,color .4s}
.reveal.in .chip{opacity:1;transform:none;transition-delay:calc(var(--c,0) * 60ms + 200ms)}
.reveal.in .chip:hover{transform:translateY(-3px)}

.marquee{
  margin-top:5rem;overflow:hidden;border-block:1px solid var(--line);
  padding:1.4rem 0;-webkit-mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);
  mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);
}
.marquee__t{display:flex;gap:3.4rem;width:max-content;animation:slide 34s linear infinite;
  animation-duration:var(--vel,34s)}
.marquee:hover .marquee__t{--vel:90s}
.marquee__t span{
  font-family:var(--f-display);font-size:1.6rem;font-weight:600;letter-spacing:.05em;
  text-transform:uppercase;color:transparent;-webkit-text-stroke:1px var(--stroke-marquee);
  white-space:nowrap;transition:-webkit-text-stroke-color .4s,color .4s;
}
.marquee__t span:hover{color:var(--accent-ink);-webkit-text-stroke-color:transparent}
.marquee__t span::after{content:"◆";margin-left:3.4rem;color:var(--accent);-webkit-text-stroke:0}
@keyframes slide{to{transform:translateX(-50%)}}

/* ================================================================
   CONTACTO
   ================================================================ */
.contact{
  position:relative;overflow:hidden;
  background:var(--grad-contact);transition:background .5s var(--ease);
}
.contact::before{
  content:"";position:absolute;inset:0;
  background:var(--grad-contact-glow);
}
/* Foco que sigue al cursor por toda la sección */
.contact::after{
  content:"";position:absolute;inset:0;pointer-events:none;opacity:0;transition:opacity .6s;
  background:radial-gradient(520px 380px at var(--cx,50%) var(--cy,50%),var(--tint),transparent 70%);
}
.contact:hover::after{opacity:1}
.contact .shell{position:relative;z-index:1}
.ccols{display:grid;grid-template-columns:repeat(3,1fr);gap:2.4rem;margin-top:3.4rem}
.ccol{position:relative;padding-top:1.4rem}
.ccol::before{
  content:"";position:absolute;top:0;left:0;height:2px;width:34px;background:var(--accent);
  transform:scaleX(0);transform-origin:left;transition:transform .7s var(--ease);
  transition-delay:var(--d,0ms);
}
.ccol.in::before{transform:scaleX(1)}
.ccol h4{
  font-family:var(--f-display);font-size:.8rem;letter-spacing:.22em;text-transform:uppercase;
  color:var(--accent-ink);margin:0 0 1.3rem;
}
.ccol a,.ccol p{display:block;color:var(--ink-2);font-weight:300;line-height:1.9;margin:0;font-size:.98rem}
.ccol a{transition:color .3s,transform .3s var(--ease)}
.ccol a:hover{color:var(--accent-ink);transform:translateX(4px)}

.cta-band{
  margin-top:4.5rem;padding:3rem;text-align:center;position:relative;overflow:hidden;
  border:1px solid var(--band-brd);background:var(--band-bg);
}
.cta-band::after{
  content:"";position:absolute;inset:0;pointer-events:none;
  background-image:
    linear-gradient(var(--grid-line) 1px,transparent 1px),
    linear-gradient(90deg,var(--grid-line) 1px,transparent 1px);
  background-size:34px 34px;
}
.cta-band>*{position:relative;z-index:1}
.cta-band h3{font-family:var(--f-display);font-size:clamp(1.5rem,3vw,2.2rem);margin:0 0 .8rem;font-weight:600}
.cta-band p{color:var(--muted);margin:0 0 2rem;font-weight:300}

/* ================================================================
   FOOTER
   ================================================================ */
.foot{background:var(--foot-bg);border-top:1px solid var(--line-3);padding:1.8rem 0;
  transition:background .5s var(--ease)}
.foot .shell{display:flex;justify-content:space-between;align-items:center;gap:1.4rem;flex-wrap:wrap}
.foot small{color:var(--foot-ink);font-size:.8rem}
.foot .soc{display:flex;gap:1rem}
.foot .soc a{color:var(--foot-ink);transition:color .3s,transform .3s}
.foot .soc a:hover{color:var(--accent-ink);transform:translateY(-2px)}

/* WhatsApp flotante */
.wa{
  position:fixed;right:22px;bottom:22px;z-index:80;width:56px;height:56px;border-radius:50%;
  background:#25D366;display:grid;place-items:center;color:#fff;
  box-shadow:0 12px 30px rgba(37,211,102,.4);transition:transform .35s var(--ease);
}
.wa:hover{transform:scale(1.1) rotate(6deg)}
.wa::after{
  content:"";position:absolute;inset:0;border-radius:50%;border:2px solid #25D366;
  animation:ping 2.4s ease-out infinite;
}

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width:980px){
  .hero .shell{grid-template-columns:1fr;gap:3rem;min-height:auto;padding:3.5rem 0 5rem;text-align:left}
  .lens{max-width:380px;order:-1}
  .svc{grid-template-columns:1fr}
  .about,.exp{grid-template-columns:1fr;gap:2.6rem}
  .ccols{grid-template-columns:1fr}
  .sect{padding:5rem 0}
  .nav__links{
    position:fixed;inset:116px 0 auto;flex-direction:column;gap:0;
    background:var(--nav-bg-stuck);
    padding:1.4rem 6vw 2rem;transform:translateY(-140%);transition:transform .45s var(--ease);
    border-bottom:1px solid var(--line);align-items:flex-start;
  }
  .nav__links.open{transform:translateY(0)}
  .nav__links a{padding:1rem 0;width:100%;border-bottom:1px solid var(--line-3)}
  .nav__burger{display:block}
  .hero__scroll{display:none}
  /* En móvil la tarjeta no se inclina: no hay cursor que seguir y el
     transform se pelea con el scroll táctil. */
  .card{transform:none}
  .card:hover{transform:translateY(-6px)}
  .circuit{display:none}
  /* En vertical el texto queda DEBAJO del lente, así que el velo tiene que
     tapar por abajo y no por la izquierda. En móvil además el video no se
     descarga: solo se ve el cartel, y el velo va sobre él. */
  .hero__scrim{
    background:linear-gradient(180deg,
      rgba(0,0,0,0) 0%, rgba(0,0,0,0) 18%,
      var(--bg) 52%, var(--bg) 100%);
  }
}
@media (max-width:560px){
  .hero__stats{gap:1.6rem}
  .stat b{font-size:1.9rem}
  .topbar .shell{justify-content:center;font-size:.7rem}
  .cta-band{padding:2rem 1.3rem}
}

/* Accesibilidad: quien pide menos movimiento, lo obtiene */
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{
    animation-duration:.01ms !important;animation-iteration-count:1 !important;
    transition-duration:.01ms !important;scroll-behavior:auto !important;
  }
  .reveal{opacity:1;transform:none}
  .hero__title .w i,.hero__sub,.hero__cta,.hero__stats,.lens__iris,.lens__cross,.lens__glint{
    opacity:1;transform:none;animation:none;
  }
  .h-sec .ltr{opacity:1;transform:none}
  .reveal .chip{opacity:1;transform:none}
  .orb,.circuit{display:none}
  .card{transform:none}
  /* Quien pide menos movimiento no quiere una intro en video ni un fondo que
     se mueve solo. El hero se queda con el cartel fijo. */
  .cargador{display:none}
  .hero__video{opacity:1}
}

/* ================================================================
   Añadidos del sitio con CMS: submenú de servicios, enlaces a las
   páginas de servicio y maquetación de la página interior.
   ================================================================ */

/* --- Submenú de servicios --- */
.nav__drop{position:relative;display:inline-block}
.nav__menu{
  position:absolute;top:calc(100% + .6rem);left:0;min-width:19rem;padding:.5rem;
  background:var(--drop-bg);border:1px solid var(--tint-2);border-radius:.7rem;
  box-shadow:var(--shadow-3);
  opacity:0;visibility:hidden;transform:translateY(-6px);transition:.22s ease;z-index:60;
}
.nav__drop:hover .nav__menu,.nav__drop:focus-within .nav__menu{opacity:1;visibility:visible;transform:none}
.nav__menu a{display:block;padding:.55rem .8rem;border-radius:.45rem;font-size:.86rem;line-height:1.35}
.nav__menu a:hover{background:var(--tint);color:var(--accent-ink)}
.nav__menu a::after{display:none}

/* En móvil el menú ya está desplegado: un hover no existe y esconder los
   servicios detrás de él los volvería inalcanzables justo donde más se navega. */
@media (max-width:900px){
  .nav__drop{display:block;width:100%}
  .nav__menu{position:static;opacity:1;visibility:visible;transform:none;box-shadow:none;
    background:transparent;border:0;padding:.2rem 0 .2rem .9rem;min-width:0}
}

/* --- Enlaces a las páginas de servicio --- */
.svc-links{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:2.4rem;justify-content:center}
.chip--link{text-decoration:none;transition:.2s ease;cursor:pointer}
.chip--link:hover{border-color:var(--accent);color:var(--accent-ink);transform:translateY(-2px)}

/* --- Página interior de servicio ---
   Ojo: estas reglas usaban var(--display), que no existe —la variable es
   --f-display—, así que los títulos de servicio salían en la tipografía
   del cuerpo en vez de la de titulares. */
.pg{padding:9rem 0 4rem;position:relative}
.pg__crumbs{font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;opacity:.65;margin-bottom:1.4rem}
.pg__crumbs a{color:var(--accent-ink)}
.pg__title{font-family:var(--f-display);font-size:clamp(2rem,5vw,3.4rem);line-height:1.06;margin:0 0 1rem}
.pg__sub{font-size:clamp(1rem,2vw,1.2rem);opacity:.85;max-width:62ch}
.pg__body{max-width:70ch;margin-top:3rem;font-size:1.02rem;line-height:1.75}
.pg__body h2{font-family:var(--f-display);font-size:1.5rem;margin:2.4rem 0 .9rem;color:var(--accent-ink)}
.pg__body p{margin:0 0 1rem}
.pg__body ul{margin:0 0 1.4rem;padding-left:1.1rem}
.pg__body li{margin:.45rem 0}
.pg__cta{margin-top:3.4rem;display:flex;flex-wrap:wrap;gap:.9rem}
.pg__otros{margin-top:4.5rem;padding-top:2.4rem;border-top:1px solid var(--line-2)}
.pg__otros h3{font-family:var(--f-display);font-size:1.15rem;margin:0 0 1.2rem;opacity:.9}
</style>
