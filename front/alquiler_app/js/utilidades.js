const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

const MILISEGUNDOS_POR_DIA = 24 * 60 * 60 * 1000;

function partesFecha(fechaIso) {
  const [anio, mes, dia] = String(fechaIso).slice(0, 10).split('-').map(Number);

  return { anio, mes, dia };
}

export function formatearFecha(fechaIso) {
  if (!fechaIso) {
    return '-';
  }

  const { anio, mes, dia } = partesFecha(fechaIso);

  return `${dia} ${MESES[mes - 1]} ${anio}`;
}

export function formatearFechaCorta(fechaIso) {
  if (!fechaIso) {
    return '-';
  }

  const { mes, dia } = partesFecha(fechaIso);

  return `${dia} ${MESES[mes - 1]}`;
}

export function contarDias(fechaInicio, fechaFin) {
  const inicio = partesFecha(fechaInicio);
  const fin = partesFecha(fechaFin);
  const utcInicio = Date.UTC(inicio.anio, inicio.mes - 1, inicio.dia);
  const utcFin = Date.UTC(fin.anio, fin.mes - 1, fin.dia);

  return Math.round((utcFin - utcInicio) / MILISEGUNDOS_POR_DIA) + 1;
}

export function hoyIso() {
  const hoy = new Date();
  const mes = String(hoy.getMonth() + 1).padStart(2, '0');
  const dia = String(hoy.getDate()).padStart(2, '0');

  return `${hoy.getFullYear()}-${mes}-${dia}`;
}

export function textoVehiculo(vehiculo) {
  return vehiculo ? `${vehiculo.marca} ${vehiculo.modelo} (${vehiculo.anio})` : '-';
}

export function pluralizar(cantidad, singular, plural) {
  return cantidad === 1 ? singular : plural;
}

export function leerConsulta() {
  return new URLSearchParams(window.location.search);
}

export function conConsulta(direccion, parametros) {
  const consulta = new URLSearchParams();

  for (const [clave, valor] of Object.entries(parametros)) {
    if (valor !== null && valor !== undefined && valor !== '') {
      consulta.set(clave, String(valor));
    }
  }

  const texto = consulta.toString();

  return texto === '' ? direccion : `${direccion}?${texto}`;
}
