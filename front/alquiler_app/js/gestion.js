import { ApiClient } from './api.js';
import { avisar, botonAccion, cabeceraPagina, enlaceBoton, estadoVacio, iniciarPagina, pintarPagina, tarjetaCliente, tarjetaVehiculo } from './componentes.js';
import { crearElemento } from './dom.js';
import { alertaGeneral, campo, campoSeleccion, eliminarRecurso, limpiarErrores, manejarEnvio } from './formularios.js';
import { leerConsulta } from './utilidades.js';

const OPCIONES_ESTADO_VEHICULO = [
  ['', 'Todos'],
  ['disponible', 'Disponible'],
  ['alquilado', 'Alquilado'],
  ['mantenimiento', 'Mantenimiento'],
];

function iniciarVehiculos(vista) {
  const consulta = leerConsulta();
  const buscar = consulta.get('buscar') ?? '';
  const estado = consulta.get('estado') ?? '';
  const lista = crearElemento('div');
  let idEditando = null;

  const titulo = crearElemento('h2', {});
  const alerta = alertaGeneral();
  const bloqueEstado = campoSeleccion({
    nombre: 'estado',
    etiqueta: 'Estado',
    ancho: true,
    opciones: [
      { valor: 'disponible', texto: 'Disponible' },
      { valor: 'mantenimiento', texto: 'En mantenimiento' },
    ],
  });
  const avisoAlquilado = crearElemento(
    'div',
    { class: 'campo campo--ancho', hidden: true },
    crearElemento('span', { class: 'campo__etiqueta' }, 'Estado'),
    crearElemento(
      'p',
      { class: 'texto-suave' },
      'Alquilado. El estado se vuelve a calcular al registrar la devolución.',
    ),
  );
  const botonGuardar = crearElemento('button', { type: 'submit', class: 'boton boton--primario' });

  const formulario = crearElemento(
    'form',
    { class: 'formulario', hidden: true },
    titulo,
    alerta,
    crearElemento(
      'div',
      { class: 'rejilla-campos' },
      campo({ nombre: 'marca', etiqueta: 'Marca *', atributos: { required: true, maxlength: 100 } }),
      campo({ nombre: 'modelo', etiqueta: 'Modelo *', atributos: { required: true, maxlength: 100 } }),
      campo({
        nombre: 'anio',
        etiqueta: 'Año *',
        tipo: 'number',
        atributos: { required: true, min: 1901, max: new Date().getFullYear() + 1 },
      }),
      campo({
        nombre: 'categoria',
        etiqueta: 'Categoría',
        atributos: { maxlength: 50, placeholder: 'Sedán, SUV, Camioneta...' },
      }),
      bloqueEstado,
      avisoAlquilado,
    ),
    crearElemento(
      'div',
      { class: 'formulario__pie' },
      crearElemento('button', { type: 'button', class: 'boton boton--secundario', onclick: cerrarFormulario }, 'Cancelar'),
      botonGuardar,
    ),
  );

  function abrirFormulario(vehiculo = null) {
    const vehiculoAlquiladoSinCambioDeEstado = vehiculo?.estado === 'alquilado';

    limpiarErrores(formulario, alerta);
    idEditando = vehiculo?.id ?? null;
    titulo.textContent = vehiculo ? 'Editar vehículo' : 'Nuevo vehículo';
    botonGuardar.textContent = vehiculo ? 'Guardar cambios' : 'Registrar';
    formulario.elements.marca.value = vehiculo?.marca ?? '';
    formulario.elements.modelo.value = vehiculo?.modelo ?? '';
    formulario.elements.anio.value = vehiculo?.anio ?? '';
    formulario.elements.categoria.value = vehiculo?.categoria ?? '';
    formulario.elements.estado.value = vehiculo?.estado === 'mantenimiento' ? 'mantenimiento' : 'disponible';
    bloqueEstado.hidden = vehiculoAlquiladoSinCambioDeEstado;
    avisoAlquilado.hidden = !vehiculoAlquiladoSinCambioDeEstado;
    formulario.hidden = false;
    formulario.elements.marca.focus();
  }

  function cerrarFormulario() {
    formulario.hidden = true;
    limpiarErrores(formulario, alerta);
  }

  manejarEnvio(formulario, alerta, async (valores) => {
    const cuerpo = {
      marca: valores.marca,
      modelo: valores.modelo,
      anio: valores.anio === '' ? '' : Number(valores.anio),
      categoria: valores.categoria,
    };

    if (valores.estado !== undefined && !bloqueEstado.hidden) {
      cuerpo.estado = valores.estado;
    }

    const { mensaje } = idEditando
      ? await ApiClient.put(`/vehiculos/${idEditando}`, cuerpo)
      : await ApiClient.post('/vehiculos', cuerpo);

    cerrarFormulario();
    avisar(mensaje);
    await recargar();
  });

  function tarjeta(vehiculo) {
    return tarjetaVehiculo(vehiculo, [
      botonAccion('Editar', 'boton--secundario', () => abrirFormulario(vehiculo)),
      enlaceBoton(
        'Historial',
        `historial.html?tipo=vehiculo&id=${vehiculo.id}`,
        'boton--chico boton--secundario',
      ),
      botonAccion('Eliminar', 'boton--texto-peligro', () =>
        eliminarRecurso({
          ruta: `/vehiculos/${vehiculo.id}`,
          titulo: `¿Eliminar ${vehiculo.marca} ${vehiculo.modelo}?`,
          mensaje: 'Esta acción no se puede deshacer.',
          recargar,
        }),
      ),
    ]);
  }

  function recargar() {
    return pintarPagina(lista, async () => {
      const { data: vehiculos } = await ApiClient.get('/vehiculos', { buscar, estado });

      return vehiculos.length === 0
        ? estadoVacio('No hay vehículos que coincidan. Registre uno con "Nuevo vehículo".')
        : crearElemento('div', { class: 'rejilla-tarjetas' }, vehiculos.map(tarjeta));
    });
  }

  const filtros = crearElemento(
    'form',
    { class: 'filtros', role: 'search', method: 'get' },
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'vehiculos-buscar' }, 'Buscar'),
      crearElemento('input', {
        class: 'campo__control',
        id: 'vehiculos-buscar',
        name: 'buscar',
        type: 'search',
        placeholder: 'Marca, modelo o categoría',
        value: buscar,
      }),
    ),
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'vehiculos-estado' }, 'Estado'),
      crearElemento(
        'select',
        { class: 'campo__control', id: 'vehiculos-estado', name: 'estado' },
        OPCIONES_ESTADO_VEHICULO.map(([valor, texto]) =>
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
        'Vehículos',
        'Flota registrada para alquiler.',
        crearElemento(
          'button',
          { type: 'button', class: 'boton boton--primario', onclick: () => abrirFormulario() },
          'Nuevo vehículo',
        ),
      ),
      formulario,
      filtros,
      lista,
    ),
  );

  return recargar();
}

export function paginaVehiculos() {
  const vista = iniciarPagina({ seccion: 'vehiculos', titulo: 'Vehículos' });

  if (vista) {
    iniciarVehiculos(vista);
  }
}

function iniciarClientes(vista) {
  const buscar = leerConsulta().get('buscar') ?? '';
  const lista = crearElemento('div');
  let idEditando = null;

  const titulo = crearElemento('h2', {});
  const alerta = alertaGeneral();
  const botonGuardar = crearElemento('button', { type: 'submit', class: 'boton boton--primario' });

  const formulario = crearElemento(
    'form',
    { class: 'formulario', hidden: true },
    titulo,
    alerta,
    crearElemento(
      'div',
      { class: 'rejilla-campos' },
      campo({
        nombre: 'nombre',
        etiqueta: 'Nombre completo *',
        ancho: true,
        atributos: { required: true, maxlength: 150 },
      }),
      campo({ nombre: 'telefono', etiqueta: 'Teléfono', tipo: 'tel', atributos: { maxlength: 50 } }),
      campo({ nombre: 'correo', etiqueta: 'Correo', tipo: 'email', atributos: { maxlength: 100 } }),
      campo({
        nombre: 'numero_licencia',
        etiqueta: 'Número de licencia',
        ancho: true,
        ayuda: 'Es obligatoria para poder hacer reservas.',
        atributos: { maxlength: 50 },
      }),
    ),
    crearElemento(
      'div',
      { class: 'formulario__pie' },
      crearElemento('button', { type: 'button', class: 'boton boton--secundario', onclick: cerrarFormulario }, 'Cancelar'),
      botonGuardar,
    ),
  );

  function abrirFormulario(cliente = null) {
    limpiarErrores(formulario, alerta);
    idEditando = cliente?.id ?? null;
    titulo.textContent = cliente ? 'Editar cliente' : 'Nuevo cliente';
    botonGuardar.textContent = cliente ? 'Guardar cambios' : 'Registrar';
    formulario.elements.nombre.value = cliente?.nombre ?? '';
    formulario.elements.telefono.value = cliente?.telefono ?? '';
    formulario.elements.correo.value = cliente?.correo ?? '';
    formulario.elements.numero_licencia.value = cliente?.numero_licencia ?? '';
    formulario.hidden = false;
    formulario.elements.nombre.focus();
  }

  function cerrarFormulario() {
    formulario.hidden = true;
    limpiarErrores(formulario, alerta);
  }

  manejarEnvio(formulario, alerta, async (valores) => {
    const cuerpo = {
      nombre: valores.nombre,
      telefono: valores.telefono,
      correo: valores.correo,
      numero_licencia: valores.numero_licencia,
    };
    const { mensaje } = idEditando
      ? await ApiClient.put(`/clientes/${idEditando}`, cuerpo)
      : await ApiClient.post('/clientes', cuerpo);

    cerrarFormulario();
    avisar(mensaje);
    await recargar();
  });

  function tarjeta(cliente) {
    return tarjetaCliente(cliente, [
      botonAccion('Editar', 'boton--secundario', () => abrirFormulario(cliente)),
      enlaceBoton(
        'Historial',
        `historial.html?tipo=cliente&id=${cliente.id}`,
        'boton--chico boton--secundario',
      ),
      botonAccion('Eliminar', 'boton--texto-peligro', () =>
        eliminarRecurso({
          ruta: `/clientes/${cliente.id}`,
          titulo: `¿Eliminar a ${cliente.nombre}?`,
          mensaje: 'Esta acción no se puede deshacer.',
          recargar,
        }),
      ),
    ]);
  }

  function recargar() {
    return pintarPagina(lista, async () => {
      const { data: clientes } = await ApiClient.get('/clientes', { buscar });

      return clientes.length === 0
        ? estadoVacio('No hay clientes que coincidan. Registre uno con "Nuevo cliente".')
        : crearElemento('div', { class: 'rejilla-tarjetas' }, clientes.map(tarjeta));
    });
  }

  const filtros = crearElemento(
    'form',
    { class: 'filtros', role: 'search', method: 'get' },
    crearElemento(
      'div',
      { class: 'campo filtros__campo' },
      crearElemento('label', { class: 'campo__etiqueta', for: 'clientes-buscar' }, 'Buscar'),
      crearElemento('input', {
        class: 'campo__control',
        id: 'clientes-buscar',
        name: 'buscar',
        type: 'search',
        placeholder: 'Nombre, correo, teléfono o licencia',
        value: buscar,
      }),
    ),
    crearElemento('button', { type: 'submit', class: 'boton boton--secundario' }, 'Buscar'),
  );

  vista.replaceChildren(
    crearElemento(
      'section',
      { class: 'pagina' },
      cabeceraPagina(
        'Clientes',
        'Personas registradas para alquilar vehículos.',
        crearElemento(
          'button',
          { type: 'button', class: 'boton boton--primario', onclick: () => abrirFormulario() },
          'Nuevo cliente',
        ),
      ),
      formulario,
      filtros,
      lista,
    ),
  );

  return recargar();
}

export function paginaClientes() {
  const vista = iniciarPagina({ seccion: 'clientes', titulo: 'Clientes' });

  if (vista) {
    iniciarClientes(vista);
  }
}
