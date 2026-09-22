<?php

namespace App\Support;

/**
 * Qué hay en la portada, contado para quien la edita y no la programó.
 *
 * Los bloques de la portada se guardan en `sitio_bloques`, y sus listas
 * (tarjetas, cifras, viñetas) viven dentro de un JSON en la columna `datos`.
 * Esa columna es libre a propósito —cada sección tiene su forma—, pero un
 * formulario no puede adivinar formas libres: hasta ahora el panel enseñaba
 * esas listas en modo lectura con un cartel de «esto se edita desde el
 * código».
 *
 * Aquí se declara la forma de cada bloque una sola vez. El panel dibuja el
 * formulario a partir de esto y el controlador valida contra esto mismo, así
 * que añadir un campo nuevo a una tarjeta es tocar este archivo y la
 * plantilla pública, y nada más.
 *
 * El texto de `nombre` y `ayuda` va dirigido a la persona de Innpro que entra
 * a cambiar un teléfono, no a un desarrollador: se describe lo que se VE en
 * la página («la franja azul de arriba»), no la estructura de datos.
 */
class PortadaEsquema
{
    /** Los iconos disponibles para las tarjetas. Han de existir en sitio/partials/icono. */
    public const ICONOS = [
        '' => 'Automático (según la posición)',
        'llave' => 'Llave — mantenimiento e instalación',
        'escudo' => 'Escudo — seguridad y protección',
        'alturas' => 'Alturas — trabajo en altura',
        'camara' => 'Cámara — Cctv y videovigilancia',
        'acceso' => 'Huella — control de acceso',
        'incendio' => 'Llama — detección de incendios',
        'soporte' => 'Diadema — soporte técnico',
    ];

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function bloques(): array
    {
        return [
            'hero' => [
                'nombre' => 'Cabecera',
                'donde' => 'La primera pantalla, con el título grande y el lente animado.',
                'ayuda' => 'Es lo único que ve mucha gente antes de decidir si sigue bajando. El título se parte solo en dos líneas y la segunda mitad se resalta en color.',
                'ancla' => 'top',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima', 'ayuda' => 'En mayúsculas y pequeñita sobre el título. Ej.: SECURE TECHNOLOGY'],
                    'titulo' => ['etiqueta' => 'Título grande', 'ayuda' => 'Escríbelo como una frase normal. La segunda mitad se resalta sola.'],
                    'texto' => ['etiqueta' => 'Párrafo de apoyo', 'tipo' => 'textarea', 'ayuda' => 'Dos o tres líneas explicando a qué se dedica la empresa.'],
                ],
                'grupos' => [
                    'cta_principal' => ['nombre' => 'Botón principal', 'ayuda' => 'El botón azul. Déjalo vacío para que no aparezca.'],
                    'cta_secundario' => ['nombre' => 'Botón secundario', 'ayuda' => 'El botón de contorno, al lado del anterior.'],
                ],
                'listas' => [
                    'estadisticas' => [
                        'nombre' => 'Cifras',
                        'ayuda' => 'Los números que suben solos al cargar. Van en una sola fila: con más de tres se aprietan y en pantallas bajas se salen de la vista.',
                        'singular' => 'cifra',
                        'max' => 4,
                        'campos' => [
                            'numero' => ['etiqueta' => 'Número', 'col' => 2, 'ayuda' => 'Solo el número, sin símbolos.'],
                            'sufijo' => ['etiqueta' => 'Símbolo detrás', 'col' => 2, 'ayuda' => 'Ej.: + o %'],
                            'etiqueta' => ['etiqueta' => 'Qué significa', 'col' => 8],
                        ],
                    ],
                ],
            ],

            'servicios' => [
                'nombre' => 'Servicios',
                'donde' => 'Las tarjetas blancas justo debajo de la cabecera.',
                'ayuda' => 'Lo que vende la empresa, resumido. Cada tarjeta puede enlazar a su página de servicio: ese enlace es lo que hace que Google le dé fuerza a esas páginas, así que conviene rellenarlo.',
                'ancla' => 'servicios',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título de la sección'],
                    'texto' => ['etiqueta' => 'Párrafo de entrada', 'tipo' => 'textarea', 'ayuda' => 'Opcional. Sale entre el título y las tarjetas. Déjalo vacío si no hace falta.'],
                ],
                'grupos' => [
                    'cta' => ['nombre' => 'Botón', 'ayuda' => 'Sale debajo del párrafo de entrada, antes de las tarjetas. Déjalo vacío para que no aparezca.'],
                ],
                'listas' => [
                    'tarjetas' => [
                        'nombre' => 'Tarjetas de servicio',
                        'ayuda' => 'Se muestran de tres en tres. Con cuatro o más, las que sobran bajan a una fila nueva.',
                        'singular' => 'tarjeta',
                        'max' => 9,
                        'campos' => [
                            'numero' => ['etiqueta' => 'Etiqueta pequeña de arriba', 'col' => 4, 'ayuda' => 'Ej.: 01 / Servicios'],
                            'icono' => ['etiqueta' => 'Icono', 'col' => 4, 'tipo' => 'select', 'opciones' => self::ICONOS],
                            'destacado' => ['etiqueta' => 'Distintivo', 'col' => 4, 'ayuda' => 'Opcional. Ej.: Más solicitado. Sale como una insignia en la esquina.'],
                            'titulo' => ['etiqueta' => 'Título', 'col' => 12],
                            'texto' => ['etiqueta' => 'Descripción', 'col' => 12, 'tipo' => 'textarea', 'filas' => 2],
                            'puntos' => [
                                'etiqueta' => 'Qué incluye',
                                'col' => 12,
                                'tipo' => 'lineas',
                                'filas' => 4,
                                'ayuda' => 'Una cosa por línea. Salen como una lista con visto dentro de la tarjeta. Déjalo vacío y la tarjeta se queda como estaba.',
                            ],
                            'url' => ['etiqueta' => 'Enlace de «Ver más»', 'col' => 12, 'ayuda' => 'Pega aquí la dirección de la página de servicio. Si lo dejas vacío, el «Ver más» aparece apagado y sin enlace.'],
                        ],
                    ],
                ],
            ],

            'acompanamiento' => [
                'nombre' => 'Acompañamiento técnico',
                'donde' => 'Las tres tarjetas de interventoría, mantenimiento y soporte, debajo de Servicios.',
                'ayuda' => 'Los servicios que no tienen página propia. Cada tarjeta cierra con su llamado a la acción, que por defecto lleva a Contáctenos.',
                'ancla' => 'acompanamiento',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título de la sección'],
                    'texto' => ['etiqueta' => 'Párrafo de entrada', 'tipo' => 'textarea', 'ayuda' => 'Opcional. Déjalo vacío si no hace falta.'],
                ],
                'listas' => [
                    'tarjetas' => [
                        'nombre' => 'Tarjetas',
                        'ayuda' => 'Se muestran de tres en tres.',
                        'singular' => 'tarjeta',
                        'max' => 6,
                        'campos' => [
                            'numero' => ['etiqueta' => 'Etiqueta pequeña de arriba', 'col' => 6, 'ayuda' => 'Ej.: 01 / Interventoría'],
                            'icono' => ['etiqueta' => 'Icono', 'col' => 6, 'tipo' => 'select', 'opciones' => self::ICONOS],
                            'titulo' => ['etiqueta' => 'Título', 'col' => 12],
                            'texto' => ['etiqueta' => 'Descripción', 'col' => 12, 'tipo' => 'textarea', 'filas' => 4],
                            'cta' => ['etiqueta' => 'Texto del enlace', 'col' => 6, 'ayuda' => 'Corto, sale en mayúsculas. Ej.: Programar mantenimiento'],
                            'url' => ['etiqueta' => 'A dónde lleva', 'col' => 6, 'ayuda' => '#contacto lleva a la sección de contacto de esta misma página.'],
                        ],
                    ],
                ],
            ],

            'empresa' => [
                'nombre' => 'Nuestra empresa',
                'donde' => 'El bloque de dos columnas con el logo dentro de un marco técnico.',
                'ayuda' => 'La presentación de la compañía. Deja una línea en blanco entre párrafos para separarlos.',
                'ancla' => 'empresa',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                    'texto' => ['etiqueta' => 'Texto', 'tipo' => 'textarea', 'filas' => 7, 'ayuda' => 'Una línea en blanco entre párrafos.'],
                ],
                'grupos' => [
                    'cta' => ['nombre' => 'Botón', 'ayuda' => 'Déjalo vacío para que no aparezca.'],
                ],
            ],

            'identidad' => [
                'nombre' => 'Misión, visión y valores',
                'donde' => 'Las tres tarjetas sin icono, debajo de Nuestra empresa.',
                'ayuda' => 'Cada tarjeta lleva un párrafo, una lista o las dos cosas: los valores quedan mejor como lista, uno por línea.',
                'ancla' => 'identidad',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                ],
                'listas' => [
                    'tarjetas' => [
                        'nombre' => 'Tarjetas',
                        'ayuda' => 'Se muestran de tres en tres.',
                        'singular' => 'tarjeta',
                        'max' => 6,
                        'campos' => [
                            'titulo' => ['etiqueta' => 'Título', 'col' => 12, 'ayuda' => 'Ej.: Misión'],
                            'texto' => ['etiqueta' => 'Texto', 'col' => 12, 'tipo' => 'textarea', 'filas' => 3],
                            'puntos' => ['etiqueta' => 'Lista', 'col' => 12, 'tipo' => 'lineas', 'filas' => 4, 'ayuda' => 'Una cosa por línea. Opcional.'],
                        ],
                    ],
                ],
            ],

            'lineamientos' => [
                'nombre' => 'Lineamientos estratégicos',
                'donde' => 'La fila de tarjetas numeradas 01, 02, 03…',
                'ayuda' => 'La numeración se calcula sola: si añades uno en medio, los demás se renumeran solos.',
                'ancla' => null,
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                ],
                'listas' => [
                    'puntos' => [
                        'nombre' => 'Lineamientos',
                        'ayuda' => 'Frases cortas. Se reparten solos en la fila.',
                        'singular' => 'lineamiento',
                        'max' => 8,
                        'campos' => [
                            'texto' => ['etiqueta' => 'Texto', 'col' => 12],
                        ],
                    ],
                ],
            ],

            'experiencia' => [
                'nombre' => 'Nuestra experiencia',
                'donde' => 'El texto largo con las etiquetas de capacidades debajo.',
                'ayuda' => 'Deja una línea en blanco entre párrafos para separarlos.',
                'ancla' => 'experiencia',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                    'texto' => ['etiqueta' => 'Texto', 'tipo' => 'textarea', 'filas' => 7, 'ayuda' => 'Una línea en blanco entre párrafos.'],
                ],
                'listas' => [
                    'chips' => [
                        'nombre' => 'Capacidades',
                        'ayuda' => 'Las etiquetas pequeñas debajo del texto.',
                        'singular' => 'capacidad',
                        'max' => 20,
                        'campos' => [
                            'texto' => ['etiqueta' => 'Nombre', 'col' => 12],
                        ],
                    ],
                    'sectores' => [
                        'nombre' => 'Sectores de la cinta',
                        'ayuda' => 'La cinta que se desplaza sola al final de la sección. Estaba escrita en el código y ya no: lo que pongas aquí es lo que se ve.',
                        'singular' => 'sector',
                        'max' => 20,
                        'campos' => [
                            'texto' => ['etiqueta' => 'Sector', 'col' => 12],
                        ],
                    ],
                ],
            ],

            'marcas' => [
                'nombre' => 'Marcas y aliados tecnológicos',
                'donde' => 'La rejilla de categorías con los fabricantes, debajo de los lineamientos.',
                'ayuda' => 'Con qué marcas trabaja la empresa, agrupadas por especialidad. Cada categoría es una tarjeta y dentro van los fabricantes, uno por línea.',
                'ancla' => 'marcas',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                    'texto' => ['etiqueta' => 'Párrafo de entrada', 'tipo' => 'textarea', 'ayuda' => 'Opcional.'],
                ],
                'listas' => [
                    'categorias' => [
                        'nombre' => 'Categorías',
                        'ayuda' => 'Cada una sale como una tarjeta con su lista de marcas.',
                        'singular' => 'categoría',
                        'max' => 12,
                        'campos' => [
                            'titulo' => ['etiqueta' => 'Categoría', 'col' => 12, 'ayuda' => 'Ej.: Videovigilancia (Cctv) y analítica'],
                            'puntos' => ['etiqueta' => 'Marcas', 'col' => 12, 'tipo' => 'lineas', 'filas' => 4, 'ayuda' => 'Una marca por línea.'],
                        ],
                    ],
                ],
            ],

            'casos' => [
                'nombre' => 'Casos de éxito',
                'donde' => 'Las tarjetas de proyectos, encima de Nuestra experiencia.',
                'ayuda' => 'Los proyectos representativos. En la portada salen los primeros cuatro; en la página «Experiencia» salen todos y se pueden filtrar por sector. La foto es opcional: sin ella la tarjeta se ve igual, con el sector de fondo.',
                'ancla' => 'casos',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                    'texto' => ['etiqueta' => 'Párrafo de entrada', 'tipo' => 'textarea', 'ayuda' => 'Opcional.'],
                ],
                'grupos' => [
                    'cta' => ['nombre' => 'Botón', 'ayuda' => 'El que lleva al listado completo. Déjalo vacío para que no aparezca.'],
                ],
                'listas' => [
                    'proyectos' => [
                        'nombre' => 'Proyectos',
                        'ayuda' => 'El orden manda: los cuatro primeros son los que se ven en la portada.',
                        'singular' => 'proyecto',
                        'max' => 40,
                        'campos' => [
                            'cliente' => ['etiqueta' => 'Cliente o proyecto', 'col' => 6, 'ayuda' => 'Ej.: Ministerio del Deporte'],
                            'sector' => ['etiqueta' => 'Sector', 'col' => 6, 'ayuda' => 'Es por lo que se filtra en la página «Experiencia». Escríbelo igual en todos los del mismo sector.'],
                            'solucion' => ['etiqueta' => 'Solución implementada', 'col' => 12, 'ayuda' => 'Ej.: Cctv + control de acceso'],
                            'imagen' => ['etiqueta' => 'Foto', 'col' => 12, 'ayuda' => 'Opcional. La dirección de una foto real del montaje. Mientras no haya, la tarjeta sale con el sector de fondo.'],
                        ],
                    ],
                ],
            ],

            'contacto' => [
                'nombre' => 'Contáctenos',
                'donde' => 'La última sección, con las tres columnas de datos.',
                'ayuda' => 'Ojo: el teléfono, la dirección y el correo NO se escriben aquí. Salen de «Datos del negocio» para que sean idénticos a los de Google, y esa coincidencia es justo lo que hace que Google se fíe del negocio.',
                'ancla' => 'contacto',
                'campos' => [
                    'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                    'titulo' => ['etiqueta' => 'Título'],
                    'texto' => ['etiqueta' => 'Texto de entrada', 'tipo' => 'textarea'],
                ],
            ],
        ];
    }

    /** El esquema de un bloque, o uno mínimo si la clave no está declarada. */
    public static function de(string $clave): array
    {
        return self::bloques()[$clave] ?? [
            'nombre' => ucfirst($clave),
            'donde' => '',
            'ayuda' => '',
            'ancla' => null,
            'campos' => [
                'antetitulo' => ['etiqueta' => 'Línea pequeña de encima'],
                'titulo' => ['etiqueta' => 'Título'],
                'texto' => ['etiqueta' => 'Texto', 'tipo' => 'textarea'],
            ],
        ];
    }
}
