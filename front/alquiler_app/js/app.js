import { paginaLogin, paginaPanel } from './inicio.js';
import { paginaVehiculos, paginaClientes } from './gestion.js';
import { paginaReservas, paginaDisponibilidad, paginaHistorial } from './reservas.js';

const PAGINAS = {
  login: paginaLogin,
  panel: paginaPanel,
  vehiculos: paginaVehiculos,
  clientes: paginaClientes,
  reservas: paginaReservas,
  disponibilidad: paginaDisponibilidad,
  historial: paginaHistorial,
};

PAGINAS[document.body.dataset.pagina]?.();
