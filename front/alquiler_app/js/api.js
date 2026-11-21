const SERVICIOS_API = [
  { prefijo: '/auth', url: 'http://127.0.0.1:8001' },
  { prefijo: '/vehiculos', url: 'http://127.0.0.1:8002' },
  { prefijo: '/clientes', url: 'http://127.0.0.1:8003' },
  { prefijo: '/reservas', url: 'http://127.0.0.1:8004' },
  { prefijo: '/resumen', url: 'http://127.0.0.1:8005' },
];

function resolverUrlServicio(ruta) {
  const servicio = SERVICIOS_API.find(({ prefijo }) => ruta === prefijo || ruta.startsWith(`${prefijo}/`));

  if (!servicio) {
    throw new Error(`No hay un microservicio configurado para la ruta ${ruta}.`);
  }

  return `${servicio.url}${ruta}`;
}

const CLAVE_TOKEN = 'alquiler_token';

export const Sesion = {
  guardar(token) {
    localStorage.setItem(CLAVE_TOKEN, token);
  },

  obtenerToken() {
    return localStorage.getItem(CLAVE_TOKEN);
  },

  haySesion() {
    return Boolean(localStorage.getItem(CLAVE_TOKEN));
  },

  limpiar() {
    localStorage.removeItem(CLAVE_TOKEN);
  },
};

export class ApiError extends Error {
  constructor(mensaje, codigoEstado, errores = null) {
    super(mensaje);
    this.name = 'ApiError';
    this.codigoEstado = codigoEstado;
    this.errores = errores;
  }
}

export function relanzarSiNoEsErrorDeApi(error) {
  if (!(error instanceof ApiError)) {
    throw error;
  }
}

export class ApiClient {
  static alNoAutorizarse = null;

  static async solicitar(metodo, ruta, { cuerpo, consulta } = {}) {
    const url = new URL(resolverUrlServicio(ruta));

    for (const [clave, valor] of Object.entries(consulta ?? {})) {
      if (valor !== null && valor !== undefined && valor !== '') {
        url.searchParams.set(clave, String(valor));
      }
    }

    const cabeceras = { Accept: 'application/json' };
    const token = Sesion.obtenerToken();

    if (token) {
      cabeceras.Authorization = `Bearer ${token}`;
    }

    if (cuerpo !== undefined) {
      cabeceras['Content-Type'] = 'application/json';
    }

    let respuesta;

    try {
      respuesta = await fetch(url, {
        method: metodo,
        headers: cabeceras,
        body: cuerpo === undefined ? null : JSON.stringify(cuerpo),
      });
    } catch {
      throw new ApiError('No se pudo conectar con el servidor. Revise su conexión.', 0);
    }

    const cuerpoRespuesta = await respuesta.json().catch(() => null);

    if (respuesta.status === 401 && ruta !== '/auth/login') {
      Sesion.limpiar();
      ApiClient.alNoAutorizarse?.();
    }

    if (!respuesta.ok || !cuerpoRespuesta?.ok) {
      const datos = cuerpoRespuesta?.data;
      const errores = datos && typeof datos === 'object' && !Array.isArray(datos) ? datos : null;

      throw new ApiError(
        cuerpoRespuesta?.mensaje ?? 'Ocurrió un error al comunicarse con el servidor.',
        respuesta.status,
        errores,
      );
    }

    return { data: cuerpoRespuesta.data, mensaje: cuerpoRespuesta.mensaje };
  }

  static get(ruta, consulta) {
    return ApiClient.solicitar('GET', ruta, { consulta });
  }

  static post(ruta, cuerpo = {}) {
    return ApiClient.solicitar('POST', ruta, { cuerpo });
  }

  static put(ruta, cuerpo = {}) {
    return ApiClient.solicitar('PUT', ruta, { cuerpo });
  }

  static delete(ruta) {
    return ApiClient.solicitar('DELETE', ruta);
  }
}
