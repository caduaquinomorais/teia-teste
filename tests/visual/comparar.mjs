// TEIA — teste visual, de responsividade e de acessibilidade (somente DESENVOLVIMENTO).
// Node 22+ e Chrome/Edge. Sem npm: usa o WebSocket nativo do Node e o Chrome DevTools Protocol.
//
//   node tests/visual/comparar.mjs http://127.0.0.1:8090
//
// Gera tests/visual/reports/relatorio.html (+ capturas, imagem de diferenças e resultado.json).

import { spawn } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync, mkdirSync, existsSync, rmSync } from 'node:fs';
import { tmpdir, platform, release, type as osType } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import zlib from 'node:zlib';

const HERE = dirname(fileURLToPath(import.meta.url));
const BASE = (process.argv[2] || 'http://127.0.0.1:8090').replace(/\/$/, '');
const REPORT_DIR = join(HERE, 'reports');
const REF_PNG = join(HERE, 'reference', 'teia-1440.png');
const GEO = JSON.parse(readFileSync(join(HERE, 'figma-geometria.json'), 'utf8'));
const WIDTHS = [320, 360, 390, 414, 768, 1024, 1280, 1440];
const TOL_SECTION = 4;
const TOL_ELEMENT = 2;
mkdirSync(REPORT_DIR, { recursive: true });

// ---------------------------------------------------------------- Chrome / CDP
function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/microsoft-edge',
  ].filter(Boolean);
  const found = candidates.find((p) => existsSync(p));
  if (!found) throw new Error('Chrome/Edge não encontrado. Defina CHROME_PATH.');
  return found;
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function launch() {
  const port = 9300 + Math.floor(Math.random() * 500);
  const profile = mkdtempSync(join(tmpdir(), 'teia-chrome-'));
  const chromePath = findChrome();
  const proc = spawn(chromePath, [
    '--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
    '--no-first-run', '--no-default-browser-check', '--disable-gpu', '--hide-scrollbars',
    '--force-device-scale-factor=1', '--disable-extensions', '--lang=pt-BR', 'about:blank',
  ], { stdio: 'ignore' });
  for (let i = 0; i < 100; i++) {
    try {
      const list = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
      const page = list.find((t) => t.type === 'page');
      if (page) return { proc, profile, ws: page.webSocketDebuggerUrl, chromePath };
    } catch { /* aguardando */ }
    await sleep(100);
  }
  throw new Error('Chrome não iniciou o DevTools.');
}

class Cdp {
  constructor(url) { this.url = url; this.id = 0; this.pending = new Map(); this.listeners = []; }
  open() {
    return new Promise((resolve, reject) => {
      this.ws = new WebSocket(this.url);
      this.ws.onopen = resolve;
      this.ws.onerror = reject;
      this.ws.onmessage = (ev) => {
        const msg = JSON.parse(ev.data);
        if (msg.id && this.pending.has(msg.id)) {
          const { resolve: res, reject: rej } = this.pending.get(msg.id);
          this.pending.delete(msg.id);
          msg.error ? rej(new Error(msg.error.message)) : res(msg.result);
        } else if (msg.method) {
          this.listeners.forEach((l) => l(msg));
        }
      };
    });
  }
  send(method, params = {}) {
    const id = ++this.id;
    this.ws.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => this.pending.set(id, { resolve, reject }));
  }
  waitFor(method, timeout = 30000) {
    return new Promise((resolve, reject) => {
      const t = setTimeout(() => reject(new Error('timeout ' + method)), timeout);
      const l = (msg) => { if (msg.method === method) { clearTimeout(t); this.listeners = this.listeners.filter((x) => x !== l); resolve(msg); } };
      this.listeners.push(l);
    });
  }
  async eval(expr) {
    const r = await this.send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true });
    if (r.exceptionDetails) throw new Error('Erro no navegador: ' + (r.exceptionDetails.exception?.description || r.exceptionDetails.text));
    return r.result.value;
  }
  close() { this.ws.close(); }
}

async function load(cdp, width, height = 900) {
  await cdp.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 768 });
  await cdp.send('Emulation.setTouchEmulationEnabled', { enabled: width < 1024 });
  const loaded = cdp.waitFor('Page.loadEventFired');
  await cdp.send('Page.navigate', { url: BASE + '/' });
  await loaded;
  await cdp.eval('document.fonts.ready.then(() => true)');
  await sleep(300);
}

// ---------------------------------------------------------------- PNG
function decodePng(buf) {
  let pos = 8; let width = 0; let height = 0; let colorType = 0; let depth = 0; const idat = [];
  while (pos < buf.length) {
    const len = buf.readUInt32BE(pos); const typ = buf.toString('ascii', pos + 4, pos + 8);
    const data = buf.subarray(pos + 8, pos + 8 + len);
    if (typ === 'IHDR') { width = data.readUInt32BE(0); height = data.readUInt32BE(4); depth = data[8]; colorType = data[9]; if (data[12] !== 0) throw new Error('PNG entrelaçado não suportado'); }
    if (typ === 'IDAT') idat.push(data);
    if (typ === 'IEND') break;
    pos += 12 + len;
  }
  if (depth !== 8 || ![2, 6].includes(colorType)) throw new Error(`PNG não suportado (profundidade ${depth}, tipo ${colorType})`);
  const bpp = colorType === 6 ? 4 : 3; const stride = width * bpp;
  const raw = zlib.inflateSync(Buffer.concat(idat));
  const out = Buffer.alloc(width * height * 4); let prev = Buffer.alloc(stride); let o = 0;
  for (let y = 0; y < height; y++) {
    const f = raw[o++]; const line = Buffer.from(raw.subarray(o, o + stride)); o += stride;
    for (let x = 0; x < stride; x++) {
      const a = x >= bpp ? line[x - bpp] : 0; const b = prev[x]; const c = x >= bpp ? prev[x - bpp] : 0;
      if (f === 1) line[x] = (line[x] + a) & 255;
      else if (f === 2) line[x] = (line[x] + b) & 255;
      else if (f === 3) line[x] = (line[x] + ((a + b) >> 1)) & 255;
      else if (f === 4) { const p = a + b - c; const pa = Math.abs(p - a); const pb = Math.abs(p - b); const pc = Math.abs(p - c); line[x] = (line[x] + (pa <= pb && pa <= pc ? a : pb <= pc ? b : c)) & 255; }
    }
    for (let x = 0; x < width; x++) {
      out[(y * width + x) * 4] = line[x * bpp]; out[(y * width + x) * 4 + 1] = line[x * bpp + 1];
      out[(y * width + x) * 4 + 2] = line[x * bpp + 2]; out[(y * width + x) * 4 + 3] = bpp === 4 ? line[x * bpp + 3] : 255;
    }
    prev = line;
  }
  return { width, height, data: out };
}

function encodePng({ width, height, data }) {
  const crc = (b) => zlib.crc32(b);
  const chunk = (type, body) => { const len = Buffer.alloc(4); len.writeUInt32BE(body.length); const tb = Buffer.concat([Buffer.from(type, 'ascii'), body]); const c = Buffer.alloc(4); c.writeUInt32BE(crc(tb) >>> 0); return Buffer.concat([len, tb, c]); };
  const ihdr = Buffer.alloc(13); ihdr.writeUInt32BE(width, 0); ihdr.writeUInt32BE(height, 4); ihdr[8] = 8; ihdr[9] = 6;
  const raw = Buffer.alloc((width * 4 + 1) * height);
  for (let y = 0; y < height; y++) { raw[y * (width * 4 + 1)] = 0; data.copy(raw, y * (width * 4 + 1) + 1, y * width * 4, (y + 1) * width * 4); }
  return Buffer.concat([Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]), chunk('IHDR', ihdr), chunk('IDAT', zlib.deflateSync(raw, { level: 6 })), chunk('IEND', Buffer.alloc(0))]);
}

// ---------------------------------------------------------------- medições no navegador
const MEASURE = (geo) => `(() => {
  const r = (el) => { if (!el) return null; const b = el.getBoundingClientRect(); return { x: b.left + scrollX, y: b.top + scrollY, w: b.width, h: b.height }; };
  const secoes = ${JSON.stringify(geo.secoes)}.map((s) => { const el = document.querySelector(s.seletor); const box = r(el);
    let bg = el ? getComputedStyle(el).backgroundColor : null; return { ...s, box, bgReal: bg }; });
  const elementos = ${JSON.stringify(geo.elementos)}.map((e) => { const sec = r(document.querySelector(e.secao)); const el = r(document.querySelector(e.seletor));
    return { ...e, real: el && sec ? { x: el.x, y: el.y - sec.y, w: el.w, h: el.h } : null }; });
  const tipografia = ${JSON.stringify(geo.tipografia)}.map((t) => { const el = document.querySelector(t.seletor); const cs = el && getComputedStyle(el);
    return { ...t, real: cs ? { tamanho: parseFloat(cs.fontSize), peso: parseInt(cs.fontWeight, 10), familia: cs.fontFamily } : null }; });
  return { secoes, elementos, tipografia, geistCarregada: document.fonts.check('600 39px Geist') && document.fonts.check('400 20px Geist'), alturaPagina: document.documentElement.scrollHeight };
})()`;

const RESPONSIVE = `(() => {
  const W = innerWidth; const out = { largura: W };
  out.rolagemHorizontal = document.documentElement.scrollWidth > document.documentElement.clientWidth + 0.5;
  out.larguraDocumento = document.documentElement.scrollWidth;
  const visible = (el) => { const cs = getComputedStyle(el); if (cs.display === 'none' || cs.visibility === 'hidden' || +cs.opacity === 0) return false; const b = el.getBoundingClientRect(); return b.width > 0 && b.height > 0 && b.bottom + scrollY > 0; };
  // Fonte mínima (elementos com texto próprio).
  let min = 99; let minEl = '';
  for (const el of document.body.querySelectorAll('*')) {
    if (!visible(el) || el.closest('.sr-only, .skip-link, iframe')) continue;
    const own = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim() !== '');
    if (!own) continue; const fs = parseFloat(getComputedStyle(el).fontSize);
    if (fs < min) { min = fs; minEl = el.tagName.toLowerCase() + '.' + el.className; }
  }
  out.fonteMinima = min; out.fonteMinimaEm = minEl;
  // Alvos de toque (só relevantes em telas de toque, < 1024 px). Links dentro de parágrafos são exceção (WCAG 2.5.8).
  const pequenos = [];
  if (W < 1024) {
    for (const el of document.querySelectorAll('a[href], button, select, input:not([type=hidden]), summary, [tabindex]:not([tabindex="-1"])')) {
      if (!visible(el) || el.closest('.skip-link') || el.closest('p, .rich')) continue;
      const b = el.getBoundingClientRect();
      if (b.width < 44 - 0.5 || b.height < 44 - 0.5) pequenos.push((el.textContent.trim() || el.getAttribute('aria-label') || el.tagName).slice(0, 40) + ' (' + Math.round(b.width) + '×' + Math.round(b.height) + ')');
    }
  }
  out.alvosPequenos = pequenos;
  return out;
})()`;

const A11Y = `(() => {
  const out = {};
  out.lang = document.documentElement.getAttribute('lang');
  out.imagensSemAlt = [...document.querySelectorAll('img')].filter((i) => !i.hasAttribute('alt')).map((i) => i.getAttribute('src'));
  out.camposSemRotulo = [...document.querySelectorAll('input:not([type=hidden]), select, textarea')].filter((el) => !(el.labels && el.labels.length) && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby')).map((el) => el.id || el.name);
  out.iframesSemTitulo = [...document.querySelectorAll('iframe')].filter((f) => !f.title).length;
  const skip = document.querySelector('a.skip-link');
  out.linkPularNavegacao = !!skip && skip.getAttribute('href') === '#conteudo' && !!document.getElementById('conteudo');
  out.h1 = document.querySelectorAll('h1').length;
  // Contraste WCAG AA.
  const parse = (c) => { const m = c.match(/rgba?\\(([^)]+)\\)/); if (!m) return null; const p = m[1].split(/[ ,\\/]+/).filter(Boolean).map(Number); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; };
  const lum = ({ r, g, b }) => { const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
  const blend = (fg, bg) => ({ r: fg.r * fg.a + bg.r * (1 - fg.a), g: fg.g * fg.a + bg.g * (1 - fg.a), b: fg.b * fg.a + bg.b * (1 - fg.a), a: 1 });
  const bgOf = (el) => { const stack = []; for (let n = el; n; n = n.parentElement) { const c = parse(getComputedStyle(n).backgroundColor); if (c && c.a > 0) { stack.push(c); if (c.a >= 1) break; } }
    let bg = { r: 255, g: 255, b: 255, a: 1 }; for (let i = stack.length - 1; i >= 0; i--) bg = blend(stack[i], bg); return bg; };
  const falhas = []; let verificados = 0;
  for (const el of document.body.querySelectorAll('*')) {
    const cs = getComputedStyle(el); if (cs.display === 'none' || cs.visibility === 'hidden') continue;
    if (el.closest('.sr-only, .skip-link, .map-card__frame, [hidden]')) continue;
    const own = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim() !== ''); if (!own) continue;
    const b = el.getBoundingClientRect(); if (!b.width) continue;
    const bg = bgOf(el); const fg = blend(parse(cs.color), bg);
    const L1 = lum(fg); const L2 = lum(bg); const ratio = (Math.max(L1, L2) + 0.05) / (Math.min(L1, L2) + 0.05);
    const size = parseFloat(cs.fontSize); const bold = parseInt(cs.fontWeight, 10) >= 700;
    const need = size >= 24 || (bold && size >= 18.66) ? 3 : 4.5; verificados++;
    if (ratio < need) falhas.push({ texto: el.textContent.trim().slice(0, 40), razao: Math.round(ratio * 100) / 100, minimo: need });
  }
  out.contrasteVerificados = verificados; out.contrasteFalhas = falhas;
  return out;
})()`;

async function keyboardCheck(cdp) {
  await cdp.eval('document.activeElement && document.activeElement.blur(); window.scrollTo(0,0); true');
  const seen = [];
  for (let i = 0; i < 14; i++) {
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9 });
    seen.push(await cdp.eval(`(() => { const el = document.activeElement; const cs = getComputedStyle(el);
      return { el: (el.textContent.trim() || el.getAttribute('aria-label') || el.tagName).slice(0, 30), foco: (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) || cs.boxShadow !== 'none' }; })()`));
  }
  return seen;
}

async function mobileMenuCheck(cdp) {
  return cdp.eval(`(async () => {
    const t = document.querySelector('.site-header__toggle'); const nav = document.querySelector('.site-header__nav');
    if (!t || getComputedStyle(t).display === 'none') return { ok: false, motivo: 'botão de menu invisível' };
    const antes = getComputedStyle(nav).display; t.click(); await new Promise(r => setTimeout(r, 50));
    const aberto = getComputedStyle(nav).display !== 'none' && t.getAttribute('aria-expanded') === 'true';
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' })); await new Promise(r => setTimeout(r, 50));
    const fechado = getComputedStyle(nav).display === 'none' && t.getAttribute('aria-expanded') === 'false';
    return { ok: antes === 'none' && aberto && fechado, antes, aberto, fechado };
  })()`);
}

async function screenshot(cdp, width, file) {
  const h = await cdp.eval('document.documentElement.scrollHeight');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width, height: Math.ceil(h), deviceScaleFactor: 1, mobile: width < 768 });
  await sleep(400);
  const shot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width, height: Math.ceil(h), scale: 1 } });
  const buf = Buffer.from(shot.data, 'base64');
  writeFileSync(file, buf);
  return buf;
}

// ---------------------------------------------------------------- comparação de pixels por seção
function pixelDiff(ref, impl, secoes) {
  const out = []; const diffImg = { width: impl.width, height: impl.height, data: Buffer.alloc(impl.width * impl.height * 4) };
  for (let i = 0; i < impl.data.length; i += 4) { const g = (impl.data[i] + impl.data[i + 1] + impl.data[i + 2]) / 3; diffImg.data[i] = diffImg.data[i + 1] = diffImg.data[i + 2] = 255 - (255 - g) * 0.35; diffImg.data[i + 3] = 255; }
  for (const s of secoes) {
    if (!s.box) continue;
    const h = Math.floor(Math.min(s.altura, s.box.h)); let diff = 0; let total = 0;
    for (let y = 0; y < h; y++) {
      const ry = Math.floor(s.y) + y; const iy = Math.floor(s.box.y) + y; if (ry >= ref.height || iy >= impl.height) break;
      for (let x = 0; x < Math.min(ref.width, impl.width); x++) {
        const a = (ry * ref.width + x) * 4; const b = (iy * impl.width + x) * 4; total++;
        if (Math.max(Math.abs(ref.data[a] - impl.data[b]), Math.abs(ref.data[a + 1] - impl.data[b + 1]), Math.abs(ref.data[a + 2] - impl.data[b + 2])) > 48) {
          diff++; diffImg.data[b] = 230; diffImg.data[b + 1] = 30; diffImg.data[b + 2] = 60;
        }
      }
    }
    out.push({ nome: s.nome, pixelsDiferentes: total ? Math.round((diff / total) * 1000) / 10 : null });
  }
  return { porSecao: out, diffImg };
}

// ---------------------------------------------------------------- execução
const hex = (c) => { const m = c && c.match(/\d+/g); return m ? '#' + m.slice(0, 3).map((v) => (+v).toString(16).padStart(2, '0')).join('').toUpperCase() : null; };
const near = (a, b, tol) => b === null || a === null || Math.abs(a - b) <= tol;

const { proc, profile, ws, chromePath } = await launch();
const cdp = new Cdp(ws);
await cdp.open();
await cdp.send('Page.enable');
await cdp.send('Runtime.enable');
const result = { base: BASE, data: new Date().toISOString(), sistema: `${osType()} ${release()} (${platform()})`, navegador: chromePath, node: process.version, larguras: {}, };

try {
  // 1) Fidelidade 1440 px.
  await load(cdp, 1440);
  await cdp.eval("document.querySelector('#duvidas details') && (document.querySelector('#duvidas details').open = true); true"); // 1º item aberto, como no Figma
  await sleep(200);
  const m = await cdp.eval(MEASURE(GEO));
  const secoes = m.secoes.map((s) => ({ ...s, okAltura: s.box && Math.abs(s.box.h - s.altura) <= TOL_SECTION, okFundo: hex(s.bgReal) === s.fundo.toUpperCase(), fundoReal: hex(s.bgReal) }));
  const elementos = m.elementos.map((e) => ({ ...e, ok: !!e.real && near(e.real.x, e.x, TOL_ELEMENT) && near(e.real.y, e.y, TOL_ELEMENT) && near(e.real.w, e.w, TOL_ELEMENT) && near(e.real.h, e.h, TOL_ELEMENT) }));
  const tipografia = m.tipografia.map((t) => ({ ...t, ok: !!t.real && t.real.tamanho === t.tamanho && t.real.peso === t.peso && /Geist/.test(t.real.familia) }));
  const implBuf = await screenshot(cdp, 1440, join(REPORT_DIR, 'implementacao-1440.png'));
  const ref = decodePng(readFileSync(REF_PNG));
  const impl = decodePng(implBuf);
  const { porSecao, diffImg } = pixelDiff(ref, impl, secoes);
  writeFileSync(join(REPORT_DIR, 'diferencas-1440.png'), encodePng(diffImg));
  writeFileSync(join(REPORT_DIR, 'figma-1440.png'), readFileSync(REF_PNG));
  result.fidelidade = { secoes, elementos, tipografia, pixels: porSecao, geistCarregada: m.geistCarregada, alturaPagina: m.alturaPagina, alturaFigma: 5388.5 };

  // 2) Responsividade + acessibilidade em todas as larguras.
  for (const w of WIDTHS) {
    await load(cdp, w);
    const r = await cdp.eval(RESPONSIVE);
    const a = await cdp.eval(A11Y);
    if (w === 390 || w === 1440) r.teclado = await keyboardCheck(cdp);
    r.menuMobile = w < 1024 ? await mobileMenuCheck(cdp) : { ok: true, motivo: 'menu completo visível' };
    if (w !== 1440) await screenshot(cdp, w, join(REPORT_DIR, `implementacao-${w}.png`));
    result.larguras[w] = { ...r, a11y: a };
  }
} finally {
  cdp.close();
  proc.kill();
  await sleep(300);
  try { rmSync(profile, { recursive: true, force: true }); } catch { /* perfil em uso */ }
}

// ---------------------------------------------------------------- avaliação
const f = result.fidelidade;
const checks = [];
const add = (grupo, nome, ok, detalhe = '') => checks.push({ grupo, nome, ok, detalhe });
f.secoes.forEach((s) => add('Altura das seções (±4 px)', s.nome, !!s.okAltura, `Figma ${s.altura} · site ${s.box ? s.box.h.toFixed(1) : '—'}`));
f.secoes.forEach((s) => add('Cor de fundo', s.nome, !!s.okFundo, `Figma ${s.fundo} · site ${s.fundoReal}`));
f.elementos.forEach((e) => add('Posição/tamanho de títulos e botões (±2 px)', e.nome, e.ok, e.real ? `Figma x${e.x} y${e.y} ${e.w ?? '—'}×${e.h} · site x${e.real.x.toFixed(1)} y${e.real.y.toFixed(1)} ${e.real.w.toFixed(1)}×${e.real.h.toFixed(1)}` : 'elemento não encontrado'));
f.tipografia.forEach((t) => add('Fonte e peso', t.nome, t.ok, t.real ? `${t.real.tamanho}px/${t.real.peso} ${t.real.familia.split(',')[0]}` : '—'));
add('Fonte e peso', 'Geist carregada (WOFF2 local)', f.geistCarregada);
for (const [w, r] of Object.entries(result.larguras)) {
  add(`Responsividade ${w} px`, 'Sem rolagem horizontal', !r.rolagemHorizontal, `largura do documento ${r.larguraDocumento}`);
  add(`Responsividade ${w} px`, 'Fonte mínima ≥ 12 px', r.fonteMinima >= 12, `${r.fonteMinima}px (${r.fonteMinimaEm})`);
  add(`Responsividade ${w} px`, 'Alvos de toque ≥ 44 px', r.alvosPequenos.length === 0, r.alvosPequenos.slice(0, 6).join('; ') || (Number(w) >= 1024 ? 'não se aplica (desktop)' : 'todos ok'));
  add(`Responsividade ${w} px`, 'Menu funcional', !!r.menuMobile.ok, JSON.stringify(r.menuMobile));
  add(`Acessibilidade ${w} px`, 'Contraste WCAG AA', r.a11y.contrasteFalhas.length === 0, `${r.a11y.contrasteVerificados} textos; falhas: ${r.a11y.contrasteFalhas.slice(0, 4).map((x) => `"${x.texto}" ${x.razao}`).join('; ') || 'nenhuma'}`);
  if (w === '1440') {
    add('Acessibilidade', 'lang="pt-BR"', r.a11y.lang === 'pt-BR');
    add('Acessibilidade', 'Todas as imagens com alt', r.a11y.imagensSemAlt.length === 0, r.a11y.imagensSemAlt.join(', '));
    add('Acessibilidade', 'Campos com rótulo', r.a11y.camposSemRotulo.length === 0, r.a11y.camposSemRotulo.join(', '));
    add('Acessibilidade', 'iframes com título', r.a11y.iframesSemTitulo === 0);
    add('Acessibilidade', 'Link “Pular para o conteúdo”', r.a11y.linkPularNavegacao);
    add('Acessibilidade', 'Um único h1', r.a11y.h1 === 1, `${r.a11y.h1}`);
  }
  if (r.teclado) {
    add(`Teclado ${w} px`, 'Primeiro Tab = pular navegação', /Pular/.test(r.teclado[0].el), r.teclado[0].el);
    add(`Teclado ${w} px`, 'Foco visível em todos os elementos', r.teclado.every((t) => t.foco), r.teclado.filter((t) => !t.foco).map((t) => t.el).join(', ') || `${r.teclado.length} elementos`);
  }
}
result.checks = checks;
const passed = checks.filter((c) => c.ok).length;
result.resumo = { aprovados: passed, total: checks.length };
writeFileSync(join(REPORT_DIR, 'resultado.json'), JSON.stringify(result, null, 2));

// ---------------------------------------------------------------- relatório HTML
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const groups = [...new Set(checks.map((c) => c.grupo))];
const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatório visual — Teia</title><style>
body{font-family:system-ui,sans-serif;margin:0;padding:24px;color:#14171F;background:#F4F5F7}h1{margin:0 0 4px}h2{margin:32px 0 8px}
.meta{color:#646B77;font-size:14px}.sum{font-size:20px;font-weight:700;margin:12px 0}
table{border-collapse:collapse;width:100%;background:#fff;font-size:14px}td,th{border:1px solid #D2D3D6;padding:6px 8px;text-align:left;vertical-align:top}
.ok{color:#1E6B34;font-weight:700}.fail{color:#B3261E;font-weight:700}
.side{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;align-items:start}.side figure{margin:0;background:#fff;padding:8px;border:1px solid #D2D3D6}.side img{width:100%;display:block}
.widths{display:flex;gap:12px;overflow-x:auto}.widths figure{margin:0;flex:0 0 auto;background:#fff;padding:6px;border:1px solid #D2D3D6}.widths img{height:720px;width:auto;display:block}
</style></head><body>
<h1>Relatório visual — Teia</h1>
<p class="meta">${esc(result.data)} · ${esc(result.sistema)} · ${esc(result.navegador)} · Node ${esc(result.node)} · ${esc(BASE)}</p>
<p class="meta">Conteúdo de teste: resposta da 1ª dúvida = texto de layout do Figma e links de PDF de teste (somente na pasta temporária de conteúdo). 1º item das Dúvidas aberto, como no Figma.</p>
<p class="sum">${passed}/${checks.length} verificações aprovadas · altura da página: site ${f.alturaPagina}px · Figma ${f.alturaFigma}px</p>
<h2>Comparação lado a lado (1440 px)</h2>
<div class="side"><figure><figcaption>Figma</figcaption><img src="figma-1440.png" alt="Captura do Figma"></figure>
<figure><figcaption>Implementação</figcaption><img src="implementacao-1440.png" alt="Captura do site"></figure>
<figure><figcaption>Diferenças (vermelho = pixel com diferença &gt; 48 em algum canal)</figcaption><img src="diferencas-1440.png" alt="Mapa de diferenças"></figure></div>
<h2>Pixels diferentes por seção</h2><table><tr><th>Seção</th><th>% de pixels diferentes</th></tr>${f.pixels.map((p) => `<tr><td>${esc(p.nome)}</td><td>${p.pixelsDiferentes}%</td></tr>`).join('')}</table>
${groups.map((g) => `<h2>${esc(g)}</h2><table><tr><th>Item</th><th>Resultado</th><th>Detalhe</th></tr>${checks.filter((c) => c.grupo === g).map((c) => `<tr><td>${esc(c.nome)}</td><td class="${c.ok ? 'ok' : 'fail'}">${c.ok ? 'OK' : 'FALHOU'}</td><td>${esc(c.detalhe)}</td></tr>`).join('')}</table>`).join('')}
<h2>Capturas por largura</h2><div class="widths">${WIDTHS.map((w) => `<figure><figcaption>${w}px</figcaption><img src="implementacao-${w}.png" alt="${w}px" loading="lazy"></figure>`).join('')}</div>
</body></html>`;
writeFileSync(join(REPORT_DIR, 'relatorio.html'), html);

console.log(`Relatório: ${join(REPORT_DIR, 'relatorio.html')}`);
console.log(`Resultado: ${passed}/${checks.length} verificações aprovadas`);
for (const c of checks.filter((x) => !x.ok)) console.log(`  FALHOU [${c.grupo}] ${c.nome} — ${c.detalhe}`);
process.exit(passed === checks.length ? 0 : 1);
