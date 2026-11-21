import { ApiClient, ApiError } from './api.js';
import { avisar, cabeceraPagina, enlaceBoton, estadoVacio, iniciarPagina, insignia, paginaError, pintarPagina, tablaReservas, tarjetaDato, tarjetaVehiculo } from './componentes.js';
import { crearElemento } from './dom.js';
import { alertaGeneral, campo, campoSeleccion, confirmar, ejecutarAccion, manejarEnvio, mostrarErrores } from './formularios.js';
import { conConsulta, contarDias, formatearFecha, hoyIso, leerConsulta, textoVehiculo } from './utilidades.js';

const OPCIONES_ESTADO_RESERVA = [
  ['activa', 'Activas'],
  ['completada', 'Completadas'],
  ['cancelada', 'Canceladas'],
  ['todas', 'Todas'],
];

function avisoConAcciones(texto, ...acciones) {
  return crearElemento(
    'div',
    {},
    estadoVacio(texto),
    crearElemento('div', { class: 'formulario__pie' }, acciones),
  );
}

function accionesReserva(recargar) {
  return {
    async alDevolver(reserva) {
      const { aceptado, mantenimiento } = await confirmar({
        titulo: 'Registrar devolución',
        mensaje: `${reserva.cliente?.nombre ?? 'El cliente'} devuelve el ${textoVehiculo(reserva.vehiculo)}.`,
        textoBoton: 'Registrar devolución',
        opcionMantenimiento: true,
      });

      if (aceptado) {
        await ejecutarAccion(
          () => ApiClient.post(`/reservas/${reserva.id}/devolucion`, { mantenimiento }),
          recargar,
        );
      }
    },

    async alCancelar(reserva) {
      const { aceptado } = await confirmar({
        titulo: `¿Cancelar la reserva #${reserva.id}?`,
        mensaje: `Reserva de ${reserva.cliente?.nombre ?? 'un cliente'}. El vehículo quedará libre.`,
        textoBoton: 'Cancelar reserva',
        peligrosa: true,
      });

      if (aceptado) {
        await ejecutarAccion(() => ApiClient.post(`/reservas/${reserva.id}/cancelacion`), recargar);
      }
    },
  };
}

function iniciarReservas(vista) {
  const consulta = leerConsulta();
  const estado = consulta.get('estado') ?? 'activa';
  const lista = crearElemento('div');
  const panel = crearElemento('div', { hidden: true });

  async function construirPanelNuevaReserva(parametros) {
    const fechaInicio = parametros.get('fecha_inicio') ?? hoyIso();
    const fechaFin = parametros.get('fecha_fin') ?? '';
    const vehiculoElegido = parametros.get('vehiculo_id') ?? '';
    const clienteElegido = parametros.get('cliente_id') ?? '';
    const hoy = hoyIso();

    const { data: clientes } = await ApiClient.get('/clientes');
    const clientesConLicencia = clientes.filter((cliente) => String(cliente.numero_licencia ?? '').trim() !== '');
    const botonCancelar = crearElemento(
      'button',
      { type: 'button', class: 'boton boton--secundario', onclick: cerrarPanel },
      'Cancelar',
    );

    if (clientes.length === 0) {
      return crearElemento(
        'div',
        { class: 'pagina pagina--angosta' },
        crearElemento('h2', {}, 'Nueva reserva'),
        avisoConAcciones(
          'Todavía no hay clientes registrados. Registre uno para poder hacer reservas.',
          botonCancelar,
          enlaceBoton('Agregar cliente', 'clientes.html?nuevo=1', 'boton--primario'),
        ),
      );
    }

    if (clientesConLicencia.length === 0) {
      return crearElemento(
        'div',
        { class: 'pagina pagina--angosta' },
        crearElemento('h2', {}, 'Nueva reserva'),
        avisoConAcciones(
          'Ningún cliente tiene licencia registrada, que es obligatoria para reservar. Agréguela desde Clientes.',
          botonCancelar,
          enlaceBoton('Ir a clientes', 'clientes.html', 'boton--primario'),
        ),
      );
    }

    const formularioPeriodo = crearElemento(
      'form',
      { class: 'formulario', method: 'get' },
      crearElemento('input', { type: 'hidden', name: 'nueva', value: 1 }),
      vehiculoElegido ? crearElemento('input', { type: 'hidden', name: 'vehiculo_id', value: vehiculoElegido }) : null,
      clienteElegido ? crearElemento('input', { type: 'hidden', name: 'cliente_id', value: clienteElegido }) : null,
      crearElemento('h2', {}, '1. Periodo'),
      crearElemento(
        'div',
        { class: 'rejilla-campos' },
        campo({
          nombre: 'fecha_inicio',
          etiqueta: 'Fecha de inicio *',
          tipo: 'date',
          valor: fechaInicio,
          atributos: { required: true, min: hoy },
        }),
        campo({
          nombre: 'fecha_fin',
          etiqueta: 'Fecha de fin *',
          tipo: 'date',
          valor: fechaFin,
          atributos: { required: true, min: hoy },
        }),
      ),
      crearElemento(
        'div',
        { class: 'formulario__pie' },
        crearElemento('button', { type: 'button', class: 'boton boton--texto', onclick: cerrarPanel }, 'Cancelar'),
        crearElemento('button', { type: 'submit', class: 'boton boton--secundario' }, 'Ver vehículos disponibles'),
      ),
    );

    const contenido = crearElemento(
      'div',
      { class: 'pagina pagina--angosta' },
      crearElemento('h2', {}, 'Nueva reserva'),
      crearElemento('p', { class: 'texto-suave' }, 'Elija las fechas para ver los vehículos disponibles.'),
      formularioPeriodo,
    );

    if (!parametros.get('fecha_fin')) {
      return contenido;
    }

    let disponibles;

    try {
      ({ data: disponibles } = await ApiClient.get('/vehiculos/disponibles', {
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
      }));
    } catch (error) {
      if (!(error instanceof ApiError) || error.codigoEstado !== 422 || !error.errores) {
        throw error;
      }

      mostrarErrores(formularioPeriodo, error.errores);

      return contenido;
    }

    const vehiculos = disponibles.vehiculos;

    if (vehiculos.length === 0) {
      const { data: registrados } = await ApiClient.get('/vehiculos');

      if (registrados.length === 0) {
        return crearElemento(
          'div',
          { class: 'pagina pagina--angosta' },
          crearElemento('h2', {}, 'Nueva reserva'),
          avisoConAcciones(
            'Todavía no hay vehículos registrados. Registre uno para poder hacer reservas.',
            botonCancelar,
            enlaceBoton('Agregar vehículo', 'vehiculos.html?nuevo=1', 'boton--primario'),
          ),
        );
      }
    }

    const alerta = alertaGeneral();

    const formularioReserva = crearElemento(
      'form',
      { class: 'formulario' },
      crearElemento('h2', {}, '2. Cliente y vehículo'),
      crearElemento(
        'p',
        { class: 'texto-suave' },
        `Del ${formatearFecha(fechaInicio)} al ${formatearFecha(fechaFin)}: ${vehiculos.length} vehículo(s) disponible(s).${vehiculos.length === 0 ? ' Pruebe con otras fechas.' : ''}`,
      ),
      alerta,
      crearElemento(
        'div',
        { class: 'rejilla-campos' },
        campoSeleccion({
          nombre: 'cliente_id',
          etiqueta: 'Cliente *',
          ancho: true,
          requerido: true,
          seleccionado: clienteElegido,
          opciones: [
            { valor: '', texto: 'Seleccione un cliente' },
            ...clientes.map((cliente) => {
              const sinLicencia = !String(cliente.numero_licencia ?? '').trim();

              return {
                valor: cliente.id,
                texto: `${cliente.nombre}${sinLicencia ? ' (sin licencia)' : ''}`,
                deshabilitada: sinLicencia,
              };
            }),
          ],
        }),
        campoSeleccion({
          nombre: 'vehiculo_id',
          etiqueta: 'Vehículo disponible *',
          ancho: true,
          requerido: true,
          seleccionado: vehiculoElegido,
          opciones: [
            {
              valor: '',
              texto:
                vehiculos.length === 0
                  ? 'No hay vehículos disponibles en esas fechas'
                  : 'Seleccione un vehículo',
            },
            ...vehiculos.map((vehiculo) => ({
              valor: vehiculo.id,
              texto: `${textoVehiculo(vehiculo)} · ${vehiculo.categoria || 'Sin categoría'}`,
            })),
          ],
        }),
      ),
      crearElemento(
        'div',
        { class: 'formulario__pie' },
        crearElemento('button', { type: 'button', class: 'boton boton--secundario', onclick: cerrarPanel }, 'Cancelar'),
        crearElemento('button', { type: 'submit', class: 'boton boton--primario' }, 'Reservar'),
      ),
    );

    manejarEnvio(formularioReserva, alerta, async (valores) => {
      const { mensaje } = await ApiClient.post('/reservas', {
        cliente_id: valores.cliente_id === '' ? '' : Number(valores.cliente_id),
        vehiculo_id: valores.vehiculo_id === '' ? '' : Number(valores.vehiculo_id),
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
      });

      cerrarPanel();
      avisar(mensaje);
      await recargar();
    });

    contenido.append(formularioReserva);

    return contenido;
  }

  async function abrirPanel(parametros = new URLSearchParams()) {
    await pintarPagina(panel, () => construirPanelNuevaReserva(parametros));
    panel.hidden = false;
  }

  function cerrarPanel() {
    panel.hidden = true;
    panel.replaceChildren();
  }

  function recargar() {
    return pintarPagina(lista, async () => {
      const { data: reservas } = await ApiClient.get('/reservas', {
        estado: estado === 'todas' ? '' : estado,
      });

      return reservas.length === 0
        ? estadoVacio('No hay reservas con ese estado.')
        : tablaReservas(reservas, { acciones: accionesReserva(recargar) });
    });
  }

  const filtros = crearElemento(
    'form',
    { class: 'filtros', method: 'get' },
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'reservas-estado' }, 'Estado'),
      crearElemento(
        'select',
        { class: 'campo__control', id: 'reservas-estado', name: 'estado' },
        OPCIONES_ESTADO_RESERVA.map(([valor, texto]) =>
          crearElemento('option', { value: valor, selected: valor === estado }, texto),
        ),
      ),
    ),
    crearElemento('button', { type: 'submit', class: 'boton boton--secundario' }, 'Filtrar'),
  );

  vista.replaceChildren(
    crearElemento(
      'section',
      { class: 'pagina' },
      cabeceraPagina(
        'Reservas',
        'Alquileres registrados, devoluciones y cancelaciones.',
        crearElemento(
          'button',
          { type: 'button', class: 'boton boton--primario', onclick: () => abrirPanel() },
          'Nueva reserva',
        ),
      ),
      panel,
      filtros,
      lista,
    ),
  );

  if (consulta.get('nueva')) {
    abrirPanel(consulta);
  }

  return recargar();
}

export function paginaReservas() {
  const vista = iniciarPagina({ seccion: 'reservas', titulo: 'Reservas' });

  if (vista) {
    iniciarReservas(vista);
  }
}

async function construirDisponibilidad() {
  const consulta = leerConsulta();
  const fechaInicio = consulta.get('fecha_inicio') ?? hoyIso();
  const fechaFin = consulta.get('fecha_fin') ?? fechaInicio;

  const filtros = crearElemento(
    'form',
    { class: 'filtros', method: 'get' },
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'campo-fecha_inicio' }, 'Desde'),
      crearElemento('input', {
        class: 'campo__control',
        id: 'campo-fecha_inicio',
        name: 'fecha_inicio',
        type: 'date',
        required: true,
        value: fechaInicio,
      }),
    ),
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'campo-fecha_fin' }, 'Hasta'),
      crearElemento('input', {
        class: 'campo__control',
        id: 'campo-fecha_fin',
        name: 'fecha_fin',
        type: 'date',
        required: true,
        value: fechaFin,
      }),
    ),
    crearElemento('button', { type: 'submit', class: 'boton boton--primario' }, 'Consultar'),
  );

  const pagina = crearElemento(
    'section',
    { class: 'pagina' },
    cabeceraPagina('Disponibilidad', 'Vehículos que se pueden reservar en un periodo.'),
    filtros,
  );

  let resultado;

  try {
    ({ data: resultado } = await ApiClient.get('/vehiculos/disponibles', {
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin,
    }));
  } catch (error) {
    if (!(error instanceof ApiError) || error.codigoEstado !== 422 || !error.errores) {
      throw error;
    }

    mostrarErrores(filtros, error.errores);

    return pagina;
  }

  const { vehiculos } = resultado;
  const textoRango =
    resultado.fecha_inicio === resultado.fecha_fin
      ? `el ${formatearFecha(resultado.fecha_inicio)}`
      : `del ${formatearFecha(resultado.fecha_inicio)} al ${formatearFecha(resultado.fecha_fin)}`;

  let avisoSinVehiculos = null;

  if (vehiculos.length === 0) {
    const { data: registrados } = await ApiClient.get('/vehiculos');

    avisoSinVehiculos =
      registrados.length === 0
        ? avisoConAcciones(
            'Todavía no hay vehículos registrados. Registre uno para poder hacer reservas.',
            enlaceBoton('Agregar vehículo', 'vehiculos.html?nuevo=1', 'boton--primario'),
          )
        : estadoVacio('No hay vehículos disponibles en ese periodo.');
  }

  pagina.append(
    crearElemento('p', { class: 'texto-suave' }, `${vehiculos.length} vehículo(s) disponible(s) ${textoRango}.`),
    vehiculos.length === 0
      ? avisoSinVehiculos
      : crearElemento(
          'div',
          { class: 'rejilla-tarjetas' },
          vehiculos.map((vehiculo) =>
            tarjetaVehiculo(
              vehiculo,
              [
                enlaceBoton(
                  'Reservar',
                  conConsulta('reservas.html', {
                    nueva: 1,
                    fecha_inicio: resultado.fecha_inicio,
                    fecha_fin: resultado.fecha_fin,
                    vehiculo_id: vehiculo.id,
                  }),
                  'boton--chico boton--primario',
                ),
              ],

              vehiculo.estado === 'alquilado'
                ? 'Hoy está alquilado; se libera antes de esa fecha.'
                : null,
            ),
          ),
        ),
  );

  return pagina;
}

export function paginaDisponibilidad() {
  const vista = iniciarPagina({ seccion: 'disponibilidad', titulo: 'Disponibilidad' });

  if (vista) {
    pintarPagina(vista, construirDisponibilidad);
  }
}

async function construirHistorial(tipo, id) {
  const esVehiculo = tipo === 'vehiculo';
  const base = esVehiculo ? `/vehiculos/${id}` : `/clientes/${id}`;
  const [{ data: propietario }, { data: reservas }] = await Promise.all([
    ApiClient.get(base),
    ApiClient.get(`${base}/historial`),
  ]);

  const contar = (estado) => reservas.filter((reserva) => reserva.estado === estado).length;
  const diasAlquilado = reservas
    .filter((reserva) => reserva.estado !== 'cancelada')
    .reduce((total, reserva) => total + contarDias(reserva.fecha_inicio, reserva.fecha_fin), 0);

  const subtitulo = esVehiculo
    ? [propietario.categoria || 'Sin categoría', ' · ', insignia(propietario.estado)]
    : [
        `Licencia: ${propietario.numero_licencia || 'sin registrar'}`,
        propietario.correo || propietario.telefono
          ? ` · ${propietario.correo || propietario.telefono}`
          : '',
      ];

  return crearElemento(
    'section',
    { class: 'pagina' },
    crearElemento(
      'header',
      { class: 'pagina__cabecera' },
      crearElemento(
        'div',
        {},
        crearElemento(
          'p',
          {},
          crearElemento(
            'a',
            { href: esVehiculo ? 'vehiculos.html' : 'clientes.html' },
            `Volver a ${esVehiculo ? 'Vehículos' : 'Clientes'}`,
          ),
        ),
        crearElemento('h1', {}, `Historial: ${esVehiculo ? textoVehiculo(propietario) : propietario.nombre}`),
        crearElemento('p', { class: 'texto-suave' }, subtitulo),
      ),
    ),
    crearElemento(
      'div',
      { class: 'tarjetas-resumen' },
      tarjetaDato('Reservas', reservas.length),
      tarjetaDato('Activas', contar('activa'), 'alquilado'),
      tarjetaDato('Completadas', contar('completada'), 'disponible'),
      tarjetaDato('Canceladas', contar('cancelada')),
      tarjetaDato('Días de alquiler', diasAlquilado),
    ),
    reservas.length === 0
      ? avisoConAcciones(
          'Todavía no hay alquileres registrados.',
          enlaceBoton(
            'Nueva reserva',
            conConsulta('reservas.html', { nueva: 1, [esVehiculo ? 'vehiculo_id' : 'cliente_id']: id }),
            'boton--primario',
          ),
        )
      : tablaReservas(reservas, { conCliente: esVehiculo, conVehiculo: !esVehiculo }),
  );
}

export function paginaHistorial() {
  const vista = iniciarPagina({
    seccion: leerConsulta().get('tipo') === 'cliente' ? 'clientes' : 'vehiculos',
    titulo: 'Historial',
  });

  if (vista) {
    const consulta = leerConsulta();
    const tipo = consulta.get('tipo');
    const id = Number(consulta.get('id'));

    if ((tipo === 'vehiculo' || tipo === 'cliente') && Number.isInteger(id) && id > 0) {
      pintarPagina(vista, () => construirHistorial(tipo, id));
    } else {
      vista.replaceChildren(paginaError(404, 'Falta el vehículo o el cliente del historial.'));
    }
  }
}
