import { ApiClient, Sesion, relanzarSiNoEsErrorDeApi } from './api.js';
import { crearElemento, icono, logo } from './dom.js';
import { contarDias, formatearFecha, textoVehiculo } from './utilidades.js';

const ETIQUETAS_ESTADO = {
  disponible: 'Disponible',
  alquilado: 'Alquilado',
  mantenimiento: 'Mantenimiento',
  activa: 'Activa',
  completada: 'Completada',
  cancelada: 'Cancelada',
  vencida: 'Vencida',
  programada: 'Programada',
};

export function insignia(estado) {
  return crearElemento('span', { class: `insignia insignia--${estado}` }, ETIQUETAS_ESTADO[estado] ?? estado);
}

export function avisar(texto, tipo = 'exito') {
  document.getElementById('avisos').replaceChildren(
    crearElemento(
      'p',
      { class: `alerta alerta--${tipo}`, role: tipo === 'error' ? 'alert' : 'status' },
      icono(tipo === 'exito' ? 'exito' : 'error'),
      crearElemento('span', {}, texto),
    ),
  );
}

export function cabeceraPagina(titulo, descripcion, accion = null) {
  return crearElemento(
    'header',
    { class: 'pagina__cabecera' },
    crearElemento(
      'div',
      {},
      crearElemento('h1', {}, titulo),
      descripcion ? crearElemento('p', { class: 'texto-suave' }, descripcion) : null,
    ),
    accion,
  );
}

export function enlaceBoton(texto, direccion, clase) {
  return crearElemento('a', { class: `boton ${clase}`, href: direccion }, texto);
}

export function botonAccion(texto, clase, alHacerClic) {
  return crearElemento(
    'button',
    { type: 'button', class: `boton boton--chico ${clase}`, onclick: alHacerClic },
    texto,
  );
}

export function estadoVacio(texto) {
  return crearElemento('p', { class: 'estado-vacio' }, texto);
}

export function paginaError(codigoEstado, mensaje) {
  return crearElemento(
    'section',
    { class: 'pagina' },
    cabeceraPagina(codigoEstado ? `Error ${codigoEstado}` : 'Sin conexión', mensaje),
    crearElemento('p', {}, enlaceBoton('Ir al inicio', 'home.html', 'boton--secundario')),
  );
}

export async function pintarPagina(vista, construir) {
  try {
    vista.replaceChildren(await construir());
  } catch (error) {
    relanzarSiNoEsErrorDeApi(error);

    const sesionYaCerrada = error.codigoEstado === 401;

    if (!sesionYaCerrada) {
      vista.replaceChildren(paginaError(error.codigoEstado, error.message));
    }
  }
}

export function tarjetaVehiculo(vehiculo, acciones, nota = null) {
  return crearElemento(
    'article',
    { class: 'tarjeta-vehiculo' },
    crearElemento(
      'header',
      { class: 'tarjeta-vehiculo__cabecera' },
      crearElemento('h3', {}, `${vehiculo.marca} ${vehiculo.modelo}`),
      insignia(vehiculo.estado),
    ),
    crearElemento('p', { class: 'texto-suave' }, `${vehiculo.anio} · ${vehiculo.categoria || 'Sin categoría'}`),
    nota ? crearElemento('small', { class: 'texto-suave' }, nota) : null,
    crearElemento('div', { class: 'tarjeta-vehiculo__acciones' }, acciones),
  );
}

export function tarjetaCliente(cliente, acciones) {
  return crearElemento(
    'article',
    { class: 'tarjeta-cliente' },
    crearElemento(
      'header',
      { class: 'tarjeta-cliente__cabecera' },
      crearElemento('h3', {}, cliente.nombre),
      cliente.numero_licencia
        ? null
        : crearElemento('span', { class: 'insignia insignia--cancelada' }, 'Sin licencia'),
    ),
    crearElemento(
      'p',
      { class: 'texto-suave' },
      `${cliente.telefono || 'Sin teléfono'} · ${cliente.correo || 'Sin correo'}`,
    ),
    cliente.numero_licencia
      ? crearElemento('small', { class: 'texto-suave' }, `Licencia: ${cliente.numero_licencia}`)
      : null,
    crearElemento('div', { class: 'tarjeta-cliente__acciones' }, acciones),
  );
}

export function tarjetaDato(etiqueta, valor, variante = null) {
  return crearElemento(
    'article',
    { class: variante ? `tarjeta-dato tarjeta-dato--${variante}` : 'tarjeta-dato' },
    crearElemento('span', { class: 'tarjeta-dato__etiqueta' }, etiqueta),
    crearElemento('strong', { class: 'tarjeta-dato__valor' }, valor),
  );
}

export function tablaReservas(reservas, { conCliente = true, conVehiculo = true, acciones = null } = {}) {
  const celda = (titulo, ...contenido) => crearElemento('td', { dataset: { titulo } }, contenido);

  const filas = reservas.map((reserva) => {
    const estadoVisible = reserva.estado_visible;
    const botones = [];

    if (acciones && reserva.estado === 'activa') {
      const puedeDevolverse = estadoVisible !== 'programada';

      if (puedeDevolverse) {
        botones.push(
          botonAccion('Registrar devolución', 'boton--primario', () => acciones.alDevolver(reserva)),
        );
      }

      botones.push(
        botonAccion('Cancelar', 'boton--texto-peligro', () => acciones.alCancelar(reserva)),
      );
    }

    return crearElemento(
      'tr',
      { class: estadoVisible === 'vencida' ? 'tabla__fila--alerta' : null },
      celda('#', reserva.id),
      conCliente ? celda('Cliente', reserva.cliente?.nombre ?? 'Cliente eliminado') : null,
      conVehiculo ? celda('Vehículo', textoVehiculo(reserva.vehiculo)) : null,
      celda('Desde', formatearFecha(reserva.fecha_inicio)),
      celda('Hasta', formatearFecha(reserva.fecha_fin)),
      celda('Días', contarDias(reserva.fecha_inicio, reserva.fecha_fin)),
      celda('Estado', insignia(estadoVisible)),
      acciones ? crearElemento('td', { class: 'tabla__acciones' }, botones) : null,
    );
  });

  return crearElemento(
    'div',
    { class: 'tabla-contenedor' },
    crearElemento(
      'table',
      { class: 'tabla' },
      crearElemento(
        'thead',
        {},
        crearElemento(
          'tr',
          {},
          crearElemento('th', {}, '#'),
          conCliente ? crearElemento('th', {}, 'Cliente') : null,
          conVehiculo ? crearElemento('th', {}, 'Vehículo') : null,
          crearElemento('th', {}, 'Desde'),
          crearElemento('th', {}, 'Hasta'),
          crearElemento('th', {}, 'Días'),
          crearElemento('th', {}, 'Estado'),
          acciones ? crearElemento('th', {}, crearElemento('span', { class: 'solo-lectores-pantalla' }, 'Acciones')) : null,
        ),
      ),
      crearElemento('tbody', {}, filas),
    ),
  );
}

const TITULO_APLICACION = 'Gestor de Alquiler';

const SECCIONES = [
  ['panel', 'home.html', 'Panel'],
  ['vehiculos', 'vehiculos.html', 'Vehículos'],
  ['clientes', 'clientes.html', 'Clientes'],
  ['reservas', 'reservas.html', 'Reservas'],
  ['disponibilidad', 'disponibilidad.html', 'Disponibilidad'],
];

function irAlInicioDeSesion() {
  window.location.replace('index.html');
}

async function cerrarSesion() {
  await ApiClient.post('/auth/logout').catch(() => null);
  Sesion.limpiar();
  irAlInicioDeSesion();
}

export function iniciarPagina({ seccion, titulo }) {
  if (!Sesion.haySesion()) {
    irAlInicioDeSesion();

    return null;
  }

  ApiClient.alNoAutorizarse = irAlInicioDeSesion;
  document.title = `${titulo} | ${TITULO_APLICACION}`;

  document.getElementById('cabecera').append(
    crearElemento(
      'div',
      { class: 'barra' },
      crearElemento('a', { class: 'barra__marca', href: 'home.html', 'aria-label': 'CarRent, ir al panel' }, logo()),
      crearElemento(
        'nav',
        { class: 'barra__navegacion', 'aria-label': 'Secciones' },
        SECCIONES.map(([clave, direccion, texto]) =>
          crearElemento(
            'a',
            {
              class: clave === seccion ? 'barra__enlace barra__enlace--activo' : 'barra__enlace',
              href: direccion,
              'aria-current': clave === seccion ? 'page' : null,
            },
            texto,
          ),
        ),
      ),
      crearElemento(
        'button',
        { type: 'button', class: 'boton boton--secundario boton--chico', onclick: cerrarSesion },
        'Cerrar sesión',
      ),
    ),
  );

  return document.getElementById('vista');
}
