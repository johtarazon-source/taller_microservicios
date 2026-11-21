const ESPACIO_DE_NOMBRES_SVG = 'http://www.w3.org/2000/svg';

export function crearElemento(etiqueta, atributos = {}, ...hijos) {
  const elemento = document.createElement(etiqueta);
  aplicarAtributos(elemento, atributos);
  agregarHijos(elemento, hijos);

  return elemento;
}

export function crearElementoSvg(etiqueta, atributos = {}, ...hijos) {
  const elemento = document.createElementNS(ESPACIO_DE_NOMBRES_SVG, etiqueta);
  aplicarAtributos(elemento, atributos);
  agregarHijos(elemento, hijos);

  return elemento;
}

function aplicarAtributos(elemento, atributos) {
  for (const [nombre, valor] of Object.entries(atributos ?? {})) {
    if (valor === null || valor === undefined || valor === false) {
      continue;
    }

    if (nombre === 'dataset') {
      Object.assign(elemento.dataset, valor);
    } else if (nombre.startsWith('on') && typeof valor === 'function') {
      elemento.addEventListener(nombre.slice(2), valor);
    } else {
      elemento.setAttribute(nombre, valor === true ? '' : String(valor));
    }
  }
}

function agregarHijos(padre, hijos) {
  for (const hijo of hijos.flat(Infinity)) {
    if (hijo === null || hijo === undefined || hijo === false) {
      continue;
    }

    padre.append(hijo instanceof Node ? hijo : String(hijo));
  }
}

const TRAZOS_ICONOS = {
  exito: ['M20 6 9 17l-5-5'],
  error: ['M18 6 6 18', 'M6 6l12 12'],
  alerta: [
    'M12 9v4',
    'M12 17h.01',
    'm10.29 3.86-8.18 14.18A1 1 0 0 0 3 19.5h18a1 1 0 0 0 .89-1.46L13.71 3.86a1 1 0 0 0-1.42 0z',
  ],
  vehiculo: [
    'M5 11l1.5-4.5A2 2 0 0 1 8.4 5h7.2a2 2 0 0 1 1.9 1.5L19 11M3 11h18v5a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-1H7v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z',
  ],
  llave: ['M13 15.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0zM21 2l-9.6 9.6M15.5 7.5l3 3L22 7l-3-3'],
  taller: [
    'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z',
  ],
  panel: ['M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z'],
  flecha: ['M7 7h10v10M7 17 17 7'],
  calendario: [
    'M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM3 10h18M8 3v4M16 3v4M12 13v5M9.5 15.5h5',
  ],
  mas: ['M12 5v14M5 12h14'],
};

export function icono(nombre, { grosor = 2, clase = 'icono' } = {}) {
  return crearElementoSvg(
    'svg',
    {
      class: clase,
      viewBox: '0 0 24 24',
      fill: 'none',
      stroke: 'currentColor',
      'stroke-width': grosor,
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'aria-hidden': 'true',
      focusable: 'false',
    },
    TRAZOS_ICONOS[nombre].map((trazo) => crearElementoSvg('path', { d: trazo })),
  );
}

export function logo() {
  return crearElemento(
    'span',
    { class: 'logo' },
    crearElementoSvg(
      'svg',
      {
        class: 'logo__marca',
        viewBox: '0 0 40 40',
        fill: 'none',
        stroke: 'currentColor',
        'stroke-linecap': 'round',
        'aria-hidden': 'true',
        focusable: 'false',
      },
      crearElementoSvg('path', { d: 'M31 10.5A14 14 0 1 0 31 29.5', 'stroke-width': 4.5 }),
      crearElementoSvg('path', { d: 'M25.5 16a6.5 6.5 0 1 0 0 8', 'stroke-width': 3.5 }),
    ),
    crearElemento('span', { class: 'logo__texto' }, 'CarRent'),
  );
}
