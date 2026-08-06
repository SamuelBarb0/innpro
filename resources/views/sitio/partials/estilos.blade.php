<style>
/* ================================================================
   INNPRO — Landing «Centro de Mando»
   Identidad heredada del panel: índigo #241D5E · azul #3488BD ·
   navy #020B3C · teal #5DCEBA · Chakra Petch + Sora.
   El motivo conductor es el lente de la cámara del logo: iris que
   se abre, barrido de radar, retículas de mira.
   ================================================================ */

:root{
  --indigo:#241D5E;  --indigo-d:#191141;
  --blue:#3488BD;    --blue-d:#12669B;
  --navy:#020B3C;    --navy-d:#01071f;
  --teal:#5DCEBA;    --teal-d:#2AA995;

  --ink:#eaf0ff;
  --muted:#9aa8c9;
  --line:rgba(93,206,186,.18);

  --f-display:'Chakra Petch','Segoe UI',sans-serif;
  --f-body:'Sora','Segoe UI',system-ui,sans-serif;

  --shell:min(1240px,92vw);
  --ease:cubic-bezier(.22,1,.36,1);
}

*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  margin:0;
  font-family:var(--f-body);
  color:var(--ink);
  background:var(--navy);
  overflow-x:hidden;
  -webkit-font-smoothing:antialiased;
}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}

/* Grano sutil sobre todo el documento: le quita el plástico al degradado */
body::after{
  content:"";position:fixed;inset:0;z-index:9999;pointer-events:none;opacity:.16;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E");
}

::selection{background:var(--teal);color:var(--navy)}
::-webkit-scrollbar{width:11px}
::-webkit-scrollbar-track{background:var(--navy-d)}
::-webkit-scrollbar-thumb{background:linear-gradient(var(--blue),var(--indigo));border-radius:9px}

/* ---------- utilidades de composición ---------- */
.shell{width:var(--shell);margin-inline:auto}
.eyebrow{
  font-family:var(--f-display);font-size:.74rem;font-weight:600;
  letter-spacing:.34em;text-transform:uppercase;color:var(--teal);
  display:flex;align-items:center;gap:.7rem;
}
.eyebrow::before{content:"";width:34px;height:1px;background:linear-gradient(90deg,transparent,var(--teal))}
.h-sec{
  font-family:var(--f-display);font-weight:600;line-height:1.05;
  font-size:clamp(2rem,4.4vw,3.4rem);margin:.7rem 0 0;letter-spacing:-.01em;
}
.lead{color:var(--muted);font-weight:300;line-height:1.75;font-size:1.03rem}

/* Corchetes de mira en las esquinas — el tic visual de la marca */
.bracket{position:relative}
.bracket::before,.bracket::after{
  content:"";position:absolute;width:16px;height:16px;pointer-events:none;
  border:1.5px solid var(--teal);opacity:.55;transition:all .45s var(--ease);
}
.bracket::before{top:-1px;left:-1px;border-right:0;border-bottom:0}
.bracket::after{bottom:-1px;right:-1px;border-left:0;border-top:0}
.bracket:hover::before,.bracket:hover::after{width:26px;height:26px;opacity:1}

/* Botones */
.btn{
  --bg:var(--blue);
  display:inline-flex;align-items:center;gap:.6rem;
  font-family:var(--f-display);font-weight:600;font-size:.86rem;
  letter-spacing:.12em;text-transform:uppercase;
  padding:.95rem 1.6rem;border:0;cursor:pointer;position:relative;overflow:hidden;
  clip-path:polygon(11px 0,100% 0,100% calc(100% - 11px),calc(100% - 11px) 100%,0 100%,0 11px);
  background:var(--bg);color:#fff;transition:transform .35s var(--ease),box-shadow .35s var(--ease);
}
.btn span{position:relative;z-index:1}
.btn::before{
  content:"";position:absolute;inset:0;z-index:0;
  background:linear-gradient(120deg,var(--indigo),var(--teal-d));
  transform:translateX(-101%);transition:transform .45s var(--ease);
}
.btn:hover{transform:translateY(-3px);box-shadow:0 14px 34px rgba(52,136,189,.34)}
.btn:hover::before{transform:translateX(0)}
.btn--ghost{background:transparent;box-shadow:inset 0 0 0 1.5px rgba(93,206,186,.5);color:var(--teal)}
.btn--ghost:hover{color:#fff;box-shadow:inset 0 0 0 1.5px transparent}

/* ================================================================
   BARRA SUPERIOR + NAV
   ================================================================ */
.topbar{
  background:var(--indigo-d);font-size:.76rem;color:#b9c6e6;
  border-bottom:1px solid rgba(255,255,255,.06);position:relative;z-index:60;
}
.topbar .shell{display:flex;gap:1.4rem;align-items:center;justify-content:flex-end;
  min-height:38px;flex-wrap:wrap}
.topbar b{color:var(--teal);font-weight:600}
.topbar .soc{display:flex;gap:.85rem;margin-left:.4rem}
.topbar .soc a{opacity:.65;transition:opacity .25s,transform .25s}
.topbar .soc a:hover{opacity:1;transform:translateY(-2px)}

.nav{
  position:sticky;top:0;z-index:50;
  background:rgba(2,11,60,.72);backdrop-filter:blur(16px) saturate(150%);
  border-bottom:1px solid rgba(255,255,255,.07);transition:all .4s var(--ease);
}
.nav.is-stuck{background:rgba(2,11,60,.94);box-shadow:0 12px 40px rgba(0,0,0,.45)}
.nav .shell{display:flex;align-items:center;gap:2rem;min-height:78px}
.nav__logo img{height:44px;width:auto}
.nav__links{display:flex;gap:1.9rem;margin-left:auto;align-items:center}
.nav__links a{
  font-family:var(--f-display);font-size:.82rem;font-weight:500;
  letter-spacing:.14em;text-transform:uppercase;color:#c3d0ee;
  position:relative;padding:.4rem 0;transition:color .3s;
}
.nav__links a::after{
  content:"";position:absolute;left:0;bottom:0;height:2px;width:0;
  background:var(--teal);transition:width .35s var(--ease);
}
.nav__links a:hover{color:#fff}
.nav__links a:hover::after{width:100%}
.nav .btn{padding:.72rem 1.25rem;font-size:.78rem}
.nav__burger{display:none;margin-left:auto;background:0;border:0;cursor:pointer;padding:.4rem}
.nav__burger span{display:block;width:26px;height:2px;background:var(--teal);margin:5px 0;transition:.3s var(--ease)}
.nav__burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.nav__burger.open span:nth-child(2){opacity:0}
.nav__burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* ================================================================
   HERO — la pieza: iris de cámara que se abre
   ================================================================ */
.hero{
  position:relative;overflow:hidden;
  background:
    radial-gradient(1200px 700px at 78% 28%,rgba(52,136,189,.28),transparent 62%),
    radial-gradient(900px 600px at 6% 84%,rgba(36,29,94,.75),transparent 60%),
    linear-gradient(165deg,#04123f 0%,var(--navy) 46%,#010726 100%);
}
/* rejilla blueprint */
.hero::before{
  content:"";position:absolute;inset:0;pointer-events:none;
  background-image:
    linear-gradient(rgba(93,206,186,.05) 1px,transparent 1px),
    linear-gradient(90deg,rgba(93,206,186,.05) 1px,transparent 1px);
  background-size:54px 54px;
  mask-image:radial-gradient(circle at 60% 45%,#000 10%,transparent 78%);
  -webkit-mask-image:radial-gradient(circle at 60% 45%,#000 10%,transparent 78%);
}
/* línea de escaneo que recorre el hero */
.hero::after{
  content:"";position:absolute;left:0;right:0;height:140px;pointer-events:none;
  background:linear-gradient(180deg,transparent,rgba(93,206,186,.09),transparent);
  animation:scan 7.5s linear infinite;
}
@keyframes scan{
  0%{transform:translateY(-160px)}
  100%{transform:translateY(105vh)}
}

.hero .shell{
  position:relative;z-index:2;
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
.hero__title em{
  font-style:normal;
  background:linear-gradient(100deg,var(--teal) 10%,var(--blue) 60%,#fff 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.hero__sub{
  margin:1.3rem 0 0;max-width:33rem;font-size:1.06rem;font-weight:300;
  line-height:1.65;color:#c2d0ee;
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
.stat b{
  display:block;font-family:var(--f-display);font-weight:700;
  font-size:clamp(1.7rem,2.4vw,2.3rem);line-height:1;color:#fff;
}
.stat b::after{content:attr(data-suf);color:var(--teal);font-size:1.3rem;vertical-align:super}
.stat span{
  display:block;margin-top:.45rem;font-size:clamp(.62rem,.9vw,.72rem);letter-spacing:.16em;
  text-transform:uppercase;color:var(--muted);line-height:1.5;
}

/* ---- el iris ---- */
.lens{position:relative;aspect-ratio:1;width:100%;max-width:520px;margin-inline:auto;
  display:grid;place-items:center}
.lens__ring{
  position:absolute;border-radius:50%;border:1px solid rgba(93,206,186,.22);
}
.lens__ring:nth-child(1){inset:0;animation:spin 34s linear infinite}
.lens__ring:nth-child(2){inset:9%;border-style:dashed;border-color:rgba(52,136,189,.3);
  animation:spin 22s linear infinite reverse}
.lens__ring:nth-child(3){inset:20%;border-color:rgba(93,206,186,.3)}
@keyframes spin{to{transform:rotate(360deg)}}

/* barrido de radar */
.lens__sweep{
  position:absolute;inset:9%;border-radius:50%;
  background:conic-gradient(from 0deg,transparent 0deg,rgba(93,206,186,.28) 34deg,transparent 62deg);
  animation:spin 4.2s linear infinite;
  mask-image:radial-gradient(circle,transparent 30%,#000 31%);
  -webkit-mask-image:radial-gradient(circle,transparent 30%,#000 31%);
}
/* pupila: se abre al cargar */
.lens__iris{
  position:relative;width:38%;aspect-ratio:1;border-radius:50%;
  background:radial-gradient(circle at 35% 30%,#8fe6d6,var(--teal) 26%,var(--blue) 58%,var(--indigo) 100%);
  box-shadow:0 0 0 10px rgba(2,11,60,.85),0 0 80px rgba(93,206,186,.45),
             inset 0 0 40px rgba(2,11,60,.6);
  transform:scale(0);animation:iris 1.5s var(--ease) .35s forwards;
}
@keyframes iris{0%{transform:scale(0) rotate(-90deg)}100%{transform:scale(1) rotate(0)}}
.lens__iris::after{
  content:"";position:absolute;inset:26%;border-radius:50%;background:var(--navy-d);
  box-shadow:inset 0 0 22px rgba(93,206,186,.5);
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
.lens__cross i{position:absolute;background:rgba(93,206,186,.3)}
.lens__cross i:nth-child(1){left:50%;top:0;bottom:0;width:1px}
.lens__cross i:nth-child(2){top:50%;left:0;right:0;height:1px}
/* nodos que orbitan */
.node{
  position:absolute;width:9px;height:9px;border-radius:50%;background:var(--teal);
  box-shadow:0 0 14px var(--teal);
  transform-origin:center;
}
.node::after{
  content:"";position:absolute;inset:-7px;border-radius:50%;
  border:1px solid rgba(93,206,186,.45);animation:ping 2.6s ease-out infinite;
}
@keyframes ping{0%{transform:scale(.5);opacity:1}100%{transform:scale(1.9);opacity:0}}
.node:nth-of-type(1){top:6%;left:48%;animation:orbit 17s linear infinite}
.node:nth-of-type(2){top:47%;left:2%;animation:orbit 23s linear infinite reverse;background:var(--blue);box-shadow:0 0 14px var(--blue)}
.node:nth-of-type(3){bottom:9%;right:14%;animation:orbit 20s linear infinite}
@keyframes orbit{to{transform:rotate(360deg) translateX(9px) rotate(-360deg)}}

.hero__scroll{
  position:absolute;bottom:26px;left:50%;transform:translateX(-50%);z-index:3;
  font-family:var(--f-display);font-size:.68rem;letter-spacing:.3em;text-transform:uppercase;
  color:var(--muted);display:grid;justify-items:center;gap:.6rem;
}
.hero__scroll i{display:block;width:1px;height:42px;background:linear-gradient(var(--teal),transparent);
  animation:drop 2s ease-in-out infinite}
@keyframes drop{0%,100%{transform:scaleY(.35);transform-origin:top}50%{transform:scaleY(1);transform-origin:top}}

/* ================================================================
   REVELADO AL HACER SCROLL
   ================================================================ */
.reveal{opacity:0;transform:translateY(38px);transition:opacity .85s var(--ease),transform .85s var(--ease);
  transition-delay:var(--d,0ms)}
.reveal.in{opacity:1;transform:none}

/* ================================================================
   SERVICIOS
   ================================================================ */
section{position:relative}
.sect{padding:6rem 0}
.sect--alt{background:linear-gradient(180deg,var(--navy) 0%,#060f36 50%,var(--navy) 100%)}

.svc{display:grid;grid-template-columns:repeat(3,1fr);gap:1.6rem;margin-top:3.4rem}
.card{
  position:relative;padding:2.6rem 2rem;overflow:hidden;
  background:linear-gradient(160deg,rgba(36,29,94,.55),rgba(2,11,60,.55));
  border:1px solid rgba(255,255,255,.07);
  transition:transform .5s var(--ease),border-color .5s,box-shadow .5s;
}
.card::before{
  content:"";position:absolute;inset:0;opacity:0;transition:opacity .5s;
  background:radial-gradient(420px 260px at var(--mx,50%) var(--my,0%),rgba(52,136,189,.22),transparent 70%);
}
.card:hover{transform:translateY(-10px);border-color:rgba(93,206,186,.4);
  box-shadow:0 26px 60px rgba(0,0,0,.45)}
.card:hover::before{opacity:1}
.card__n{
  font-family:var(--f-display);font-size:.72rem;letter-spacing:.28em;color:var(--teal);
  position:relative;z-index:1;
}
.card__ico{
  width:56px;height:56px;margin:1.4rem 0 1.5rem;position:relative;z-index:1;
  display:grid;place-items:center;color:var(--teal);
  background:rgba(93,206,186,.09);border:1px solid rgba(93,206,186,.24);
  clip-path:polygon(12px 0,100% 0,100% calc(100% - 12px),calc(100% - 12px) 100%,0 100%,0 12px);
  transition:transform .5s var(--ease),background .4s;
}
.card:hover .card__ico{transform:rotate(-8deg) scale(1.08);background:rgba(93,206,186,.18)}
.card h3{
  font-family:var(--f-display);font-weight:600;font-size:1.32rem;line-height:1.25;
  margin:0 0 .9rem;position:relative;z-index:1;
}
.card p{margin:0;color:var(--muted);font-weight:300;line-height:1.7;font-size:.95rem;
  position:relative;z-index:1}
.card__more{
  display:inline-flex;align-items:center;gap:.5rem;margin-top:1.6rem;position:relative;z-index:1;
  font-family:var(--f-display);font-size:.78rem;letter-spacing:.16em;text-transform:uppercase;
  color:var(--teal);transition:gap .3s var(--ease);
}
.card:hover .card__more{gap:.95rem}

/* ================================================================
   EMPRESA — dos columnas con marco técnico
   ================================================================ */
.about{display:grid;grid-template-columns:.95fr 1.05fr;gap:4rem;align-items:center}
.about__art{
  position:relative;aspect-ratio:4/3.4;
  background:
    radial-gradient(circle at 30% 25%,rgba(52,136,189,.35),transparent 58%),
    linear-gradient(150deg,var(--indigo),var(--navy));
  border:1px solid rgba(93,206,186,.22);
  display:grid;place-items:center;overflow:hidden;
}
.about__art::before{
  content:"";position:absolute;inset:0;
  background-image:
    linear-gradient(rgba(93,206,186,.07) 1px,transparent 1px),
    linear-gradient(90deg,rgba(93,206,186,.07) 1px,transparent 1px);
  background-size:26px 26px;
}
.about__art img{position:relative;width:66%;filter:drop-shadow(0 18px 40px rgba(0,0,0,.55))}
.about__art .pulse{
  position:absolute;width:58%;aspect-ratio:1;border-radius:50%;
  border:1px solid rgba(93,206,186,.35);animation:ping 3.4s ease-out infinite;
}
.about__art .pulse:nth-of-type(2){animation-delay:1.7s}

/* ================================================================
   LINEAMIENTOS ESTRATÉGICOS
   ================================================================ */
.guide{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.3rem;margin-top:3.4rem}
.guide__i{
  padding:1.9rem 1.5rem;position:relative;
  background:rgba(255,255,255,.028);border-left:2px solid var(--blue);
  transition:background .45s,border-color .45s,transform .45s var(--ease);
}
.guide__i:hover{background:rgba(93,206,186,.07);border-left-color:var(--teal);transform:translateX(6px)}
.guide__i b{
  font-family:var(--f-display);font-size:2.3rem;font-weight:700;line-height:1;
  color:transparent;-webkit-text-stroke:1px rgba(93,206,186,.55);display:block;margin-bottom:.9rem;
}
.guide__i p{margin:0;font-size:.95rem;line-height:1.6;font-weight:300;color:#d3ddf5}

/* ================================================================
   EXPERIENCIA — capacidades en cinta + texto
   ================================================================ */
.exp{display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start}
.chips{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:2rem}
.chip{
  font-family:var(--f-display);font-size:.78rem;letter-spacing:.06em;
  padding:.6rem 1rem;color:#cfe0ff;
  background:rgba(52,136,189,.11);border:1px solid rgba(52,136,189,.3);
  transition:all .4s var(--ease);
}
.chip:hover{background:var(--blue);color:#fff;transform:translateY(-3px)}

.marquee{
  margin-top:5rem;overflow:hidden;border-block:1px solid var(--line);
  padding:1.4rem 0;-webkit-mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);
  mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);
}
.marquee__t{display:flex;gap:3.4rem;width:max-content;animation:slide 34s linear infinite}
.marquee__t span{
  font-family:var(--f-display);font-size:1.6rem;font-weight:600;letter-spacing:.05em;
  text-transform:uppercase;color:transparent;-webkit-text-stroke:1px rgba(178,197,238,.8);
  white-space:nowrap;
}
.marquee__t span::after{content:"◆";margin-left:3.4rem;color:var(--teal);-webkit-text-stroke:0}
@keyframes slide{to{transform:translateX(-50%)}}

/* ================================================================
   CONTACTO
   ================================================================ */
.contact{
  position:relative;overflow:hidden;
  background:linear-gradient(140deg,var(--indigo) 0%,#0d1a55 55%,var(--navy) 100%);
}
.contact::before{
  content:"";position:absolute;inset:0;
  background:radial-gradient(700px 420px at 82% 18%,rgba(93,206,186,.16),transparent 65%);
}
.contact .shell{position:relative;z-index:1}
.ccols{display:grid;grid-template-columns:repeat(3,1fr);gap:2.4rem;margin-top:3.4rem}
.ccol h4{
  font-family:var(--f-display);font-size:.8rem;letter-spacing:.22em;text-transform:uppercase;
  color:var(--teal);margin:0 0 1.3rem;
}
.ccol a,.ccol p{display:block;color:#d6e0f7;font-weight:300;line-height:1.9;margin:0;font-size:.98rem}
.ccol a{transition:color .3s,transform .3s var(--ease)}
.ccol a:hover{color:var(--teal);transform:translateX(4px)}

.cta-band{
  margin-top:4.5rem;padding:3rem;text-align:center;
  border:1px solid rgba(93,206,186,.28);background:rgba(2,11,60,.42);
}
.cta-band h3{font-family:var(--f-display);font-size:clamp(1.5rem,3vw,2.2rem);margin:0 0 .8rem;font-weight:600}
.cta-band p{color:var(--muted);margin:0 0 2rem;font-weight:300}

/* ================================================================
   FOOTER
   ================================================================ */
.foot{background:var(--navy-d);border-top:1px solid rgba(255,255,255,.06);padding:1.8rem 0}
.foot .shell{display:flex;justify-content:space-between;align-items:center;gap:1.4rem;flex-wrap:wrap}
.foot small{color:#7b88a8;font-size:.8rem}
.foot .soc{display:flex;gap:1rem}
.foot .soc a{color:#7b88a8;transition:color .3s,transform .3s}
.foot .soc a:hover{color:var(--teal);transform:translateY(-2px)}

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
    background:rgba(2,11,60,.985);backdrop-filter:blur(18px);
    padding:1.4rem 6vw 2rem;transform:translateY(-140%);transition:transform .45s var(--ease);
    border-bottom:1px solid var(--line);align-items:flex-start;
  }
  .nav__links.open{transform:translateY(0)}
  .nav__links a{padding:1rem 0;width:100%;border-bottom:1px solid rgba(255,255,255,.06)}
  .nav__burger{display:block}
  .hero__scroll{display:none}
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
}

/* ================================================================
   Añadidos del sitio con CMS: submenú de servicios, enlaces a las
   páginas de servicio y maquetación de la página interior.
   ================================================================ */

/* --- Submenú de servicios --- */
.nav__drop{position:relative;display:inline-block}
.nav__menu{
  position:absolute;top:calc(100% + .6rem);left:0;min-width:19rem;padding:.5rem;
  background:rgba(2,11,60,.97);border:1px solid rgba(93,206,186,.22);border-radius:.7rem;
  box-shadow:0 18px 40px rgba(0,0,0,.45);
  opacity:0;visibility:hidden;transform:translateY(-6px);transition:.22s ease;z-index:60;
}
.nav__drop:hover .nav__menu,.nav__drop:focus-within .nav__menu{opacity:1;visibility:visible;transform:none}
.nav__menu a{display:block;padding:.55rem .8rem;border-radius:.45rem;font-size:.86rem;line-height:1.35}
.nav__menu a:hover{background:rgba(93,206,186,.12);color:var(--teal)}

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
.chip--link:hover{border-color:var(--teal);color:var(--teal);transform:translateY(-2px)}

/* --- Página interior de servicio --- */
.pg{padding:9rem 0 4rem;position:relative}
.pg__crumbs{font-size:.78rem;letter-spacing:.06em;text-transform:uppercase;opacity:.65;margin-bottom:1.4rem}
.pg__crumbs a{color:var(--teal)}
.pg__title{font-family:var(--display);font-size:clamp(2rem,5vw,3.4rem);line-height:1.06;margin:0 0 1rem}
.pg__sub{font-size:clamp(1rem,2vw,1.2rem);opacity:.85;max-width:62ch}
.pg__body{max-width:70ch;margin-top:3rem;font-size:1.02rem;line-height:1.75}
.pg__body h2{font-family:var(--display);font-size:1.5rem;margin:2.4rem 0 .9rem;color:var(--teal)}
.pg__body p{margin:0 0 1rem}
.pg__body ul{margin:0 0 1.4rem;padding-left:1.1rem}
.pg__body li{margin:.45rem 0}
.pg__cta{margin-top:3.4rem;display:flex;flex-wrap:wrap;gap:.9rem}
.pg__otros{margin-top:4.5rem;padding-top:2.4rem;border-top:1px solid rgba(255,255,255,.1)}
.pg__otros h3{font-family:var(--display);font-size:1.15rem;margin:0 0 1.2rem;opacity:.9}
</style>
