async (page) => {
  const VOLCAR = null;
  const base = 'http://localhost:8080/taller_microservicios/front/alquiler_app/';
  const CORS = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Authorization, Content-Type, Accept',
    'Access-Control-Allow-Methods': 'GET,POST,PUT,DELETE,OPTIONS',
  };

  const vehiculos = [
    { id: 1, marca: 'Kia', modelo: 'Rio', anio: 2024, categoria: 'Hatchback', estado: 'disponible', created_at: '2026-11-19T22:25:19Z', updated_at: '2026-11-19T22:25:19Z' },
    { id: 2, marca: 'Nissan', modelo: 'Kick', anio: 2022, categoria: 'SUV', estado: 'alquilado', created_at: '2026-11-19T12:00:45Z', updated_at: '2026-11-19T12:00:45Z' },
    { id: 3, marca: 'Toyota', modelo: 'Corolla Cross', anio: 2023, categoria: 'SUV', estado: 'mantenimiento', created_at: '2026-11-18T14:33:28Z', updated_at: '2026-11-18T14:33:28Z' },
  ];
  const clientes = [
    { id: 1, nombre: 'Ana Prueba', telefono: '3001112233', correo: 'ana@example.com', numero_licencia: 'LIC-1', created_at: '2026-11-19T10:00:00Z', updated_at: '2026-11-19T10:00:00Z' },
    { id: 2, nombre: 'Luis Muestra', telefono: null, correo: null, numero_licencia: null, created_at: '2026-11-19T11:00:00Z', updated_at: '2026-11-19T11:00:00Z' },
  ];
  const reservas = [
    { id: 1, cliente_id: 1, vehiculo_id: 2, fecha_inicio: '2026-11-19', fecha_fin: '2026-11-25', estado: 'activa', estado_visible: 'activa', created_at: '2026-11-19T12:10:00Z', updated_at: '2026-11-19T12:10:00Z', cliente: clientes[0], vehiculo: vehiculos[1] },
    { id: 2, cliente_id: 1, vehiculo_id: 1, fecha_inicio: '2026-11-10', fecha_fin: '2026-11-12', estado: 'completada', estado_visible: 'completada', created_at: '2026-11-09T09:00:00Z', updated_at: '2026-11-12T18:00:00Z', cliente: clientes[0], vehiculo: vehiculos[0] },
    { id: 3, cliente_id: 2, vehiculo_id: 1, fecha_inicio: '2026-12-01', fecha_fin: '2026-12-05', estado: 'activa', estado_visible: 'programada', created_at: '2026-11-20T09:00:00Z', updated_at: '2026-11-20T09:00:00Z', cliente: clientes[1], vehiculo: vehiculos[0] },
    { id: 4, cliente_id: 2, vehiculo_id: 3, fecha_inicio: '2026-11-01', fecha_fin: '2026-11-03', estado: 'cancelada', estado_visible: 'cancelada', created_at: '2026-10-30T09:00:00Z', updated_at: '2026-10-31T09:00:00Z', cliente: clientes[1], vehiculo: vehiculos[2] },
  ];

  const peticiones = [];
  await page.route(/^http:\/\/127\.0\.0\.1:800[1-5]\//, async (route) => {
    const req = route.request();
    const url = new URL(req.url());
    const metodo = req.method();
    const p = url.pathname;
    if (metodo === 'OPTIONS') return route.fulfill({ status: 204, headers: CORS });
    peticiones.push(`${metodo} ${p}${url.search}`);
    const responder = (data, mensaje = 'Operación correcta.', status = 200) =>
      route.fulfill({ status, headers: { ...CORS, 'Content-Type': 'application/json' }, body: JSON.stringify({ ok: true, mensaje, data }) });
    const fallar = (status, mensaje) =>
      route.fulfill({ status, headers: { ...CORS, 'Content-Type': 'application/json' }, body: JSON.stringify({ ok: false, mensaje, data: null }) });
    let m;
    if (p === '/auth/login' && metodo === 'POST') return responder({ token: 'tok-prueba', usuario: 'admin' }, 'Sesión iniciada.');
    if (p === '/auth/logout') return responder(null, 'Sesión cerrada.');
    if (p === '/resumen') {
      return responder({
        fecha: '2026-11-20', clientes_activos: 2, reservas_total: 4, recientes: reservas.slice(0, 3), marcas: ['Kia', 'Nissan', 'Toyota'],
        vehiculos: { total: 3, disponible: 1, alquilado: 1, mantenimiento: 1 }, clientes: 2, reservas_activas: 2, vencidas: [reservas[0]],
      });
    }
    if (p === '/vehiculos/disponibles') {
      return responder({ fecha_inicio: url.searchParams.get('fecha_inicio'), fecha_fin: url.searchParams.get('fecha_fin'), vehiculos: [vehiculos[0]] });
    }
    if ((m = p.match(/^\/vehiculos\/(\d+)\/historial$/))) return responder(reservas.filter((r) => r.vehiculo_id === Number(m[1])));
    if ((m = p.match(/^\/vehiculos\/(\d+)$/))) {
      if (metodo === 'GET') return responder(vehiculos.find((v) => v.id === Number(m[1])) ?? vehiculos[0]);
      if (metodo === 'PUT') return responder({ ...vehiculos[0], ...JSON.parse(req.postData() ?? '{}') }, 'Vehículo actualizado correctamente.');
      if (metodo === 'DELETE') return responder(null, 'Vehículo eliminado correctamente.');
    }
    if (p === '/vehiculos') {
      if (metodo === 'POST') return responder({ id: 9, ...JSON.parse(req.postData() ?? '{}'), estado: 'disponible' }, 'Vehículo registrado correctamente.', 201);
      const estado = url.searchParams.get('estado');
      return responder(estado ? vehiculos.filter((v) => v.estado === estado) : vehiculos);
    }
    if ((m = p.match(/^\/clientes\/(\d+)\/historial$/))) return responder(reservas.filter((r) => r.cliente_id === Number(m[1])));
    if ((m = p.match(/^\/clientes\/(\d+)$/))) {
      if (metodo === 'GET') return responder(clientes.find((c) => c.id === Number(m[1])) ?? clientes[0]);
      if (metodo === 'PUT') return responder({ ...clientes[0], ...JSON.parse(req.postData() ?? '{}') }, 'Cliente actualizado correctamente.');
      if (metodo === 'DELETE') return responder(null, 'Cliente eliminado correctamente.');
    }
    if (p === '/clientes') {
      if (metodo === 'POST') return responder({ id: 9, ...JSON.parse(req.postData() ?? '{}') }, 'Cliente registrado correctamente.', 201);
      return responder(clientes);
    }
    if ((m = p.match(/^\/reservas\/(\d+)\/(devolucion|cancelacion)$/))) return responder(reservas[0], m[2] === 'devolucion' ? 'Devolución registrada.' : 'Reserva cancelada.');
    if (p === '/reservas') {
      if (metodo === 'POST') return responder(reservas[2], 'Reserva creada correctamente.', 201);
      const estado = url.searchParams.get('estado');
      return responder(estado ? reservas.filter((r) => r.estado === estado) : reservas);
    }
    return fallar(404, 'Ruta sin simular: ' + p);
  });

  const errores = [];
  const fallidas = [];
  page.on('console', (mensaje) => { if (mensaje.type() === 'error') errores.push(mensaje.text().slice(0, 140)); });
  page.on('pageerror', (e) => errores.push('pageerror: ' + String(e).slice(0, 140)));
  page.on('requestfailed', (r) => fallidas.push('FAIL ' + r.url().replace(/^https?:\/\/[^/]+/, '')));
  page.on('response', (r) => { if (r.status() >= 400 && !/127\.0\.0\.1:800/.test(r.url())) fallidas.push(`${r.status()} ${r.url().replace(/^https?:\/\/[^/]+/, '')}`); });

  const resultado = {};
  const paso = async (nombre, accion) => {
    peticiones.length = 0; errores.length = 0; fallidas.length = 0;
    let nota = '';
    try { if (accion) await accion(); } catch (e) { nota = 'ACCION FALLO: ' + String(e).split('\n')[0].slice(0, 100); }
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(350);
    const foto = await page.evaluate(async () => {
      const hash = async (t) => [...new Uint8Array(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(t)))].slice(0, 6).map((b) => b.toString(16).padStart(2, '0')).join('');
      const html = (s) => document.querySelector(s)?.outerHTML ?? '';
      return {
        pagina: document.body.dataset.pagina,
        ruta: location.pathname.split('/').pop() + location.search,
        cuerpo: await hash(document.body.outerHTML.replace('app.js?v=6', 'app.js?v=4')),
        vista: await hash(html('#vista')),
        longitud: html('#vista').length,
        texto: document.body.innerText.replace(/\s+/g, ' ').slice(0, 70),
        dialogo: document.querySelectorAll('dialog[open], [role="dialog"]').length,
      };
    });
    resultado[nombre] = { ...foto, peticiones: [...peticiones].sort(), errores: [...errores], fallidas: [...fallidas], nota };
    if (VOLCAR === nombre) resultado.__html = await page.evaluate(() => document.body.outerHTML);
  };
  const clic = async (nombre, opciones = {}) => page.getByRole('button', { name: nombre, ...opciones }).first().click({ timeout: 3000 });
  const rellenar = async (formulario) => { for (const [n, v] of Object.entries(formulario)) await page.locator(`[name="${n}"]`).first().fill(String(v), { timeout: 3000 }); };

  await page.goto(base + 'index.html', { waitUntil: 'networkidle' });
  await page.evaluate(() => localStorage.clear());
  await paso('01 login', async () => { await page.goto(base + 'index.html', { waitUntil: 'networkidle' }); });
  await paso('02 login incorrecto vacio', async () => { await page.locator('form [type=submit]').first().click(); });
  await paso('03 login ok -> home', async () => {
    await page.fill('#usuario', 'admin'); await page.fill('#contrasena', 'x');
    await Promise.all([page.waitForURL(/home\.html/, { timeout: 8000 }), page.locator('form [type=submit]').first().click()]);
  });
  await paso('04 panel', async () => { await page.goto(base + 'home.html'); });
  await paso('05 vehiculos', async () => { await page.goto(base + 'vehiculos.html'); });
  await paso('06 vehiculos filtro estado', async () => { await page.goto(base + 'vehiculos.html?estado=disponible'); });
  await paso('07 vehiculos nuevo (formulario)', async () => { await page.goto(base + 'vehiculos.html'); await page.waitForLoadState('networkidle'); await clic('Nuevo vehículo'); });
  await paso('08 vehiculos guardar', async () => { await rellenar({ marca: 'Mazda', modelo: '3', anio: 2024, categoria: 'Sedán' }); await page.locator('form:has([name="marca"]) button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('09 vehiculos editar (formulario)', async () => { await clic('Editar'); });
  await paso('09b vehiculos editar (guardar)', async () => { await page.locator('form:has([name="marca"]) button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('10 vehiculos eliminar (confirmar)', async () => { await page.goto(base + 'vehiculos.html'); await page.waitForLoadState('networkidle'); await clic('Eliminar'); });
  await paso('11 vehiculos eliminar (aceptar)', async () => { await page.locator('dialog button, [role="dialog"] button').filter({ hasText: /^Eliminar$/ }).first().click({ timeout: 3000 }); });
  await paso('12 clientes', async () => { await page.goto(base + 'clientes.html'); });
  await paso('13 clientes nuevo (formulario)', async () => { await clic('Nuevo cliente'); });
  await paso('14 clientes guardar', async () => { await rellenar({ nombre: 'Eva Nueva', telefono: '300', correo: 'e@e.co', numero_licencia: 'L-9' }); await page.locator('form:has([name="nombre"]) button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('14b clientes editar (formulario)', async () => { await clic('Editar'); });
  await paso('14c clientes editar (guardar)', async () => { await page.locator('form:has([name="nombre"]) button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('14d clientes eliminar (aceptar)', async () => { await clic('Eliminar'); await page.locator('dialog[open] button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('15 reservas', async () => { await page.goto(base + 'reservas.html'); });
  await paso('16 reservas filtro estado', async () => { await page.goto(base + 'reservas.html?estado=activa'); });
  await paso('17 reservas nueva (formulario)', async () => { await page.goto(base + 'reservas.html'); await page.waitForLoadState('networkidle'); await clic('Nueva reserva'); });
  await paso('18 reservas devolucion (dialogo)', async () => { await page.goto(base + 'reservas.html?estado=activa'); await page.waitForLoadState('networkidle'); await page.getByRole('button', { name: /devol/i }).first().click({ timeout: 3000 }); });
  await paso('19 reservas cancelar (dialogo)', async () => { await page.goto(base + 'reservas.html?estado=activa'); await page.waitForLoadState('networkidle'); await page.getByRole('button', { name: /cancelar/i }).first().click({ timeout: 3000 }); });
  await paso('19b reservas cancelar (aceptar)', async () => { await page.locator('dialog[open] button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('19c reservas devolver (aceptar con mantenimiento)', async () => { await page.goto(base + 'reservas.html?estado=activa'); await page.waitForLoadState('networkidle'); await page.getByRole('button', { name: /devol/i }).first().click({ timeout: 3000 }); await page.locator('dialog[open] input[name="mantenimiento"]').check({ timeout: 3000 }); await page.locator('dialog[open] button[type=submit]').first().click({ timeout: 3000 }); });
  await paso('20 disponibilidad', async () => { await page.goto(base + 'disponibilidad.html'); });
  await paso('21 disponibilidad consulta', async () => { await page.goto(base + 'disponibilidad.html?fecha_inicio=2026-12-01&fecha_fin=2026-12-05'); });
  await paso('22 historial vehiculo', async () => { await page.goto(base + 'historial.html?tipo=vehiculo&id=2'); });
  await paso('23 historial cliente', async () => { await page.goto(base + 'historial.html?tipo=cliente&id=1'); });
  await paso('24 historial sin parametros', async () => { await page.goto(base + 'historial.html'); });
  await paso('25 cerrar sesion', async () => { await page.goto(base + 'home.html'); await page.waitForLoadState('networkidle'); await clic('Cerrar sesión'); });
  await paso('26 sin sesion -> login', async () => { await page.goto(base + 'vehiculos.html'); });

  if (resultado.__html) return resultado.__html;
  const BASE = {
    '01 login': 'db1a40e852e0|e3b0c44298fc|0', '02 login incorrecto vacio': 'f1f60730b65a|e3b0c44298fc|0',
    '03 login ok -> home': 'a774beafadb2|559d8d3c15c6|8013', '04 panel': 'a774beafadb2|559d8d3c15c6|8013',
    '05 vehiculos': '6ba4a349811e|0296fdd8c5eb|4271', '06 vehiculos filtro estado': '204a97b22bc7|83a7dd3059b9|3188',
    '07 vehiculos nuevo (formulario)': 'd703bf03202f|04a972417af9|4284', '08 vehiculos guardar': '8f8386420741|96d203b41bf9|4294',
    '09 vehiculos editar (formulario)': 'c84e919e3c09|2d9087788f2a|4291', '09b vehiculos editar (guardar)': '9a6d7bfa8a70|cb14822d6dc8|4301',
    '10 vehiculos eliminar (confirmar)': '8e18d074d90b|0296fdd8c5eb|4271', '11 vehiculos eliminar (aceptar)': '08ba7efe38cf|0296fdd8c5eb|4271',
    '12 clientes': 'c18cb23e92a0|d81b96089533|3007', '13 clientes nuevo (formulario)': '393e6c52e3e9|f5929f8399bf|3019',
    '14 clientes guardar': 'd8fb1acb79d9|3147db26badb|3029', '14b clientes editar (formulario)': 'de28df936395|4da1dab6c7f3|3026',
    '14c clientes editar (guardar)': '64f273067a29|fdf06b2f7f41|3036', '14d clientes eliminar (aceptar)': 'eeaaa68dcc3a|fdf06b2f7f41|3036',
    '15 reservas': '9aff3509c195|f36d0af342d9|2023', '16 reservas filtro estado': '9aff3509c195|f36d0af342d9|2023',
    '17 reservas nueva (formulario)': '9f2d27194a16|882458255264|2945', '18 reservas devolucion (dialogo)': '3e0fb6373a3b|f36d0af342d9|2023',
    '19 reservas cancelar (dialogo)': '78327d6724a0|f36d0af342d9|2023', '19b reservas cancelar (aceptar)': 'bf90049a8d6d|f36d0af342d9|2023',
    '19c reservas devolver (aceptar con mantenimiento)': '707459c29c5c|f36d0af342d9|2023', '20 disponibilidad': 'ff991db4fac8|afd6251035a7|1287',
    '21 disponibilidad consulta': '0811043fb6ee|9b320394b530|1301', '22 historial vehiculo': 'e87d6d8df264|670dd9d69481|1534',
    '23 historial cliente': '5487f4cb66a0|91f2919c1c7a|1784', '24 historial sin parametros': 'fd2c5c2607bf|98051ed2cee5|274',
    '25 cerrar sesion': 'db1a40e852e0|e3b0c44298fc|0', '26 sin sesion -> login': 'db1a40e852e0|e3b0c44298fc|0',
  };
  const lineas = Object.entries(resultado).map(([n, r]) => {
    const obtenido = `${r.cuerpo}|${r.vista}|${r.longitud}`;
    const igual = BASE[n] === obtenido;
    const problemas = [r.errores.join('|'), r.fallidas.filter((f) => !/woff2/.test(f)).join('|'), r.nota].filter(Boolean).join(' / ');
    return `${igual ? 'IGUAL  ' : 'DIFERE '} ${n} [${r.peticiones.join('; ')}]${igual ? '' : ' obtenido=' + obtenido + ' esperado=' + BASE[n]}${problemas ? ' PROBLEMAS: ' + problemas : ''}`;
  });
  const diferentes = lineas.filter((l) => l.startsWith('DIFERE')).length;
  return `${Object.keys(resultado).length} pasos, ${diferentes} distintos de la línea base\n` + lineas.join('\n');
}
