<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitor de Sensores ESP32</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
  :root {
    --bg: #0b1120; --panel: #121a2e; --panel2: #18223a; --borde: #24304d;
    --texto: #e6ebf5; --suave: #8b97b3;
    --temp: #ff6b6b; --hum: #4dabf7; --luz: #ffd43b; --gas: #69db7c;
    --x: #ff6b6b; --y: #69db7c; --z: #4dabf7;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--texto); font-family: Inter, system-ui, sans-serif; min-height: 100vh; }
  .contenedor { max-width: 1400px; margin: 0 auto; padding: 24px 16px 40px; }

  header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
  h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; }
  h1 span { color: var(--suave); font-weight: 500; font-size: .95rem; display: block; margin-top: 2px; }
  .acciones { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
  .estado { display: flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: var(--panel); border: 1px solid var(--borde); font-size: .85rem; font-weight: 500; }
  .punto { width: 10px; height: 10px; border-radius: 50%; background: #868e96; }
  .vivo .punto { background: #40c057; box-shadow: 0 0 0 0 #40c05799; animation: pulso 1.5s infinite; }
  .caido .punto { background: #fa5252; }
  @keyframes pulso { 70% { box-shadow: 0 0 0 8px #40c05700; } 100% { box-shadow: 0 0 0 0 #40c05700; } }
  .btn { padding: 8px 14px; border-radius: 8px; background: var(--panel2); border: 1px solid var(--borde); color: var(--texto); font: inherit; font-size: .85rem; cursor: pointer; text-decoration: none; }
  .btn:hover { border-color: var(--suave); }
  select.btn { appearance: none; }

  .aviso { display: none; background: #3b1f24; border: 1px solid #7a2e38; color: #ffc9c9; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: .9rem; }

  .tarjetas { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
  .tarjeta { background: var(--panel); border: 1px solid var(--borde); border-radius: 14px; padding: 18px; position: relative; overflow: hidden; }
  .tarjeta::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--c); }
  .tarjeta .nombre { color: var(--suave); font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }
  .tarjeta .valor { font-size: 2rem; font-weight: 700; margin-top: 6px; font-variant-numeric: tabular-nums; }
  .tarjeta .valor small { font-size: .95rem; color: var(--suave); font-weight: 500; margin-left: 4px; }
  .tarjeta .extra { color: var(--suave); font-size: .8rem; margin-top: 6px; font-variant-numeric: tabular-nums; }
  .barra { height: 6px; background: var(--panel2); border-radius: 3px; margin-top: 10px; overflow: hidden; }
  .barra div { height: 100%; width: 0; background: var(--c); transition: width .4s; }

  .graficos { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 560px), 1fr)); gap: 16px; margin-bottom: 20px; }
  .panel { background: var(--panel); border: 1px solid var(--borde); border-radius: 14px; padding: 18px; min-width: 0; }
  .panel h2 { font-size: .95rem; font-weight: 600; margin-bottom: 12px; }
  .lienzo { position: relative; height: 260px; }

  .tabla-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: .85rem; font-variant-numeric: tabular-nums; }
  th, td { padding: 9px 10px; text-align: right; white-space: nowrap; border-bottom: 1px solid var(--borde); }
  th:first-child, td:first-child { text-align: left; }
  th { color: var(--suave); font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
  tbody tr:first-child { background: var(--panel2); }
  footer { color: var(--suave); font-size: .8rem; text-align: center; margin-top: 24px; }
</style>
</head>
<body>
<div class="contenedor">
  <header>
    <h1>Monitor de Sensores <span>ESP32 · DHT11 · LDR · Gas · MPU6050</span></h1>
    <div class="acciones">
      <select class="btn" id="rango" title="Cantidad de lecturas en los graficos">
        <option value="30">30 s</option>
        <option value="60" selected>1 min</option>
        <option value="300">5 min</option>
        <option value="600">10 min</option>
      </select>
      <a class="btn" href="sensores_general/datos_sensores.txt" download>Descargar .txt</a>
      <div class="estado" id="estado"><div class="punto"></div><span id="estadoTxt">Conectando…</span></div>
    </div>
  </header>

  <div class="aviso" id="aviso"></div>

  <section class="tarjetas">
    <div class="tarjeta" style="--c: var(--temp)"><div class="nombre">Temperatura</div><div class="valor"><span id="vTemp">--</span><small>°C</small></div><div class="extra" id="eTemp">mín -- · máx --</div></div>
    <div class="tarjeta" style="--c: var(--hum)"><div class="nombre">Humedad</div><div class="valor"><span id="vHum">--</span><small>%</small></div><div class="barra"><div id="bHum"></div></div></div>
    <div class="tarjeta" style="--c: var(--luz)"><div class="nombre">Luz (LDR)</div><div class="valor"><span id="vLuz">--</span><small>/ 4095</small></div><div class="barra"><div id="bLuz"></div></div></div>
    <div class="tarjeta" style="--c: var(--gas)"><div class="nombre">Gas</div><div class="valor"><span id="vGas">--</span><small>/ 4095</small></div><div class="barra"><div id="bGas"></div></div></div>
    <div class="tarjeta" style="--c: #b197fc"><div class="nombre">Aceleración total</div><div class="valor"><span id="vAcc">--</span><small>g</small></div><div class="extra" id="eAcc">X -- · Y -- · Z --</div></div>
    <div class="tarjeta" style="--c: #f783ac"><div class="nombre">Giro total</div><div class="valor"><span id="vGyr">--</span><small>°/s</small></div><div class="extra" id="eGyr">X -- · Y -- · Z --</div></div>
  </section>

  <section class="graficos">
    <div class="panel"><h2>Temperatura y humedad</h2><div class="lienzo"><canvas id="gClima"></canvas></div></div>
    <div class="panel"><h2>Luz y gas</h2><div class="lienzo"><canvas id="gLuzGas"></canvas></div></div>
    <div class="panel"><h2>Acelerómetro (g)</h2><div class="lienzo"><canvas id="gAcc"></canvas></div></div>
    <div class="panel"><h2>Giroscopio (°/s)</h2><div class="lienzo"><canvas id="gGyr"></canvas></div></div>
  </section>

  <section class="panel">
    <h2>Últimas lecturas</h2>
    <div class="tabla-wrap">
      <table>
        <thead><tr><th>Fecha y hora</th><th>Temp °C</th><th>Hum %</th><th>Luz</th><th>Gas</th><th>Acc X</th><th>Acc Y</th><th>Acc Z</th><th>Gyro X</th><th>Gyro Y</th><th>Gyro Z</th></tr></thead>
        <tbody id="tabla"></tbody>
      </table>
    </div>
  </section>

  <footer>Se actualiza cada segundo · Datos de <code>sensores_general/datos_sensores.txt</code></footer>
</div>

<script>
const css = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
Chart.defaults.color = css('--suave');
Chart.defaults.borderColor = '#24304d';
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.animation = false;

function crearGrafico(id, series, ejes) {
  return new Chart(document.getElementById(id), {
    type: 'line',
    data: { labels: [], datasets: series.map(s => ({
      label: s.label, data: [], borderColor: s.color, backgroundColor: s.color + '22',
      fill: !!s.fill, tension: .35, pointRadius: 0, borderWidth: 2, yAxisID: s.eje || 'y'
    })) },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { boxWidth: 12, usePointStyle: true } } },
      scales: Object.assign({ x: { ticks: { maxTicksLimit: 6, maxRotation: 0 }, grid: { display: false } } }, ejes || { y: {} })
    }
  });
}

const g = {
  clima: crearGrafico('gClima', [
    { label: 'Temperatura °C', color: css('--temp'), fill: true },
    { label: 'Humedad %', color: css('--hum'), eje: 'y2' }
  ], { y: { title: { display: true, text: '°C' } }, y2: { position: 'right', min: 0, max: 100, grid: { display: false }, title: { display: true, text: '%' } } }),
  luzgas: crearGrafico('gLuzGas', [
    { label: 'Luz', color: css('--luz'), fill: true },
    { label: 'Gas', color: css('--gas'), fill: true }
  ], { y: { min: 0, max: 4095 } }),
  acc: crearGrafico('gAcc', [
    { label: 'X', color: css('--x') }, { label: 'Y', color: css('--y') }, { label: 'Z', color: css('--z') }
  ]),
  gyr: crearGrafico('gGyr', [
    { label: 'X', color: css('--x') }, { label: 'Y', color: css('--y') }, { label: 'Z', color: css('--z') }
  ])
};

const $ = id => document.getElementById(id);
const f1 = v => v.toFixed(1), f2 = v => v.toFixed(2);
const pct = v => Math.max(0, Math.min(100, v)) + '%';

function poner(grafico, etiquetas, columnas, datos) {
  grafico.data.labels = etiquetas;
  columnas.forEach((c, i) => grafico.data.datasets[i].data = datos.map(d => d[c]));
  grafico.update('none');
}

function estado(clase, texto) {
  $('estado').className = 'estado ' + clase;
  $('estadoTxt').textContent = texto;
}

async function actualizar() {
  try {
    const r = await fetch('api.php?n=' + $('rango').value, { cache: 'no-store' });
    const j = await r.json();
    if (!j.ok || !j.datos.length) {
      $('aviso').style.display = 'block';
      $('aviso').textContent = j.error || 'El archivo existe pero aún no tiene lecturas. ¿Está corriendo iniciar_guardado.bat?';
      estado('caido', 'Sin datos');
      return;
    }
    const d = j.datos, u = d[d.length - 1];
    const sinDatos = j.segundos_sin_datos > 5;
    $('aviso').style.display = sinDatos ? 'block' : 'none';
    if (sinDatos) $('aviso').textContent = 'No llegan datos nuevos hace ' + j.segundos_sin_datos + ' s. Revisa que el ESP32 esté conectado y que iniciar_guardado.bat esté abierto (y el Monitor Serie cerrado).';
    estado(sinDatos ? 'caido' : 'vivo', sinDatos ? 'Detenido' : 'En vivo · ' + u.fecha.slice(11));

    const temps = d.map(x => x.temp);
    $('vTemp').textContent = f1(u.temp);
    $('eTemp').textContent = 'mín ' + f1(Math.min(...temps)) + ' · máx ' + f1(Math.max(...temps));
    $('vHum').textContent = f1(u.hum);  $('bHum').style.width = pct(u.hum);
    $('vLuz').textContent = u.luz;      $('bLuz').style.width = pct(u.luz / 40.95);
    $('vGas').textContent = u.gas;      $('bGas').style.width = pct(u.gas / 40.95);
    $('vAcc').textContent = f2(Math.hypot(u.ax, u.ay, u.az));
    $('eAcc').textContent = 'X ' + f2(u.ax) + ' · Y ' + f2(u.ay) + ' · Z ' + f2(u.az);
    $('vGyr').textContent = f1(Math.hypot(u.gx, u.gy, u.gz));
    $('eGyr').textContent = 'X ' + f1(u.gx) + ' · Y ' + f1(u.gy) + ' · Z ' + f1(u.gz);

    const et = d.map(x => x.fecha.slice(11));
    poner(g.clima, et, ['temp', 'hum'], d);
    poner(g.luzgas, et, ['luz', 'gas'], d);
    poner(g.acc, et, ['ax', 'ay', 'az'], d);
    poner(g.gyr, et, ['gx', 'gy', 'gz'], d);

    $('tabla').innerHTML = d.slice(-10).reverse().map(x =>
      '<tr><td>' + x.fecha + '</td><td>' + f1(x.temp) + '</td><td>' + f1(x.hum) + '</td><td>' + x.luz + '</td><td>' + x.gas +
      '</td><td>' + f2(x.ax) + '</td><td>' + f2(x.ay) + '</td><td>' + f2(x.az) +
      '</td><td>' + f1(x.gx) + '</td><td>' + f1(x.gy) + '</td><td>' + f1(x.gz) + '</td></tr>').join('');
  } catch (e) {
    estado('caido', 'Error');
    $('aviso').style.display = 'block';
    $('aviso').textContent = 'No se pudo leer api.php. ¿Está encendido Apache en XAMPP?';
  }
}

actualizar();
setInterval(actualizar, 1000);
$('rango').addEventListener('change', actualizar);
</script>
</body>
</html>
