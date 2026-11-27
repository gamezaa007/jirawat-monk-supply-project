/* Dashboard ผลสำรวจลูกค้า ร้านจิรวัสสังฆภัณฑ์ โคราช (รายวิชา 1101033)
 * อ่านข้อมูลจากไฟล์ CSV + schema แล้ววาดกราฟด้วย Chart.js — กรองตามเพศ/อายุได้ */
(function () {
  const root = document.getElementById('jw-dash');
  if (!root || !window.Chart) return;
  const css = getComputedStyle(document.documentElement);
  const C = {
    ink: '#2A1A12', ink2: '#5C4636', line: '#E6D8BE', paper: '#FBF6EC',
    series: ['#8E2A1B', '#D9821E', '#B8893A', '#5E6B3A', '#3F5A6B', '#7A5A86', '#A8784F'],
  };
  Chart.defaults.font.family = "'IBM Plex Sans Thai Looped', sans-serif";
  Chart.defaults.font.size = 13;
  Chart.defaults.color = C.ink2;
  Chart.defaults.plugins.legend.labels.boxWidth = 12;
  Chart.defaults.plugins.tooltip.backgroundColor = C.ink;
  Chart.defaults.plugins.tooltip.padding = 10;

  const LEVEL = (x) => x >= 4.21 ? 'มากที่สุด' : x >= 3.41 ? 'มาก' : x >= 2.61 ? 'ปานกลาง' : x >= 1.81 ? 'น้อย' : 'น้อยที่สุด';
  const fmt = (x, d = 2) => Number.isFinite(x) ? x.toFixed(d) : '–';
  const pct = (a, b) => b ? (100 * a / b) : 0;

  function parseCSV(text) {
    text = text.replace(/^﻿/, '');
    const rows = []; let row = [], cur = '', q = false;
    for (let i = 0; i < text.length; i++) {
      const ch = text[i];
      if (q) { if (ch === '"') { if (text[i + 1] === '"') { cur += '"'; i++; } else q = false; } else cur += ch; }
      else if (ch === '"') q = true;
      else if (ch === ',') { row.push(cur); cur = ''; }
      else if (ch === '\n' || ch === '\r') { if (ch === '\r' && text[i + 1] === '\n') i++; row.push(cur); rows.push(row); row = []; cur = ''; }
      else cur += ch;
    }
    if (cur || row.length) { row.push(cur); rows.push(row); }
    const head = rows.shift();
    return rows.filter(r => r.length === head.length).map(r => Object.fromEntries(head.map((h, i) => [h, r[i]])));
  }

  const charts = {};
  function draw(id, cfg) {
    if (charts[id]) { charts[id].data = cfg.data; charts[id].options = cfg.options; charts[id].update(); return; }
    charts[id] = new Chart(document.getElementById(id), cfg);
  }
  const countBy = (rows, key, opts) => opts.map(o => rows.filter(r => r[key] === o).length);
  const avg = (arr) => arr.length ? arr.reduce((a, b) => a + b, 0) / arr.length : NaN;
  const sd = (arr) => { const m = avg(arr); return Math.sqrt(arr.reduce((a, b) => a + (b - m) ** 2, 0) / Math.max(1, arr.length - 1)); };

  function barCfg(labels, data, { horizontal = false, total = 0, color = C.series[0], max } = {}) {
    return {
      type: 'bar',
      data: { labels, datasets: [{ data, backgroundColor: color, borderRadius: 4, maxBarThickness: 34 }] },
      options: {
        indexAxis: horizontal ? 'y' : 'x', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => total ? `${c.raw} คน (${fmt(pct(c.raw, total), 1)}%)` : fmt(c.raw) } } },
        scales: {
          [horizontal ? 'x' : 'y']: { beginAtZero: true, max, grid: { color: C.line }, ticks: { precision: 0 } },
          [horizontal ? 'y' : 'x']: { grid: { display: false }, ticks: { autoSkip: false } },
        },
      },
    };
  }
  function donutCfg(labels, data) {
    return {
      type: 'doughnut',
      data: { labels, datasets: [{ data, backgroundColor: C.series, borderColor: C.paper, borderWidth: 3 }] },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '62%',
        plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => { const t = c.dataset.data.reduce((a, b) => a + b, 0); return `${c.label}: ${c.raw} คน (${fmt(pct(c.raw, t), 1)}%)`; } } } },
      },
    };
  }

  function render(all, S) {
    const g = root.querySelector('#jw-f-gender').value, a = root.querySelector('#jw-f-age').value;
    const rows = all.filter(r => (!g || r.gender === g) && (!a || r.age === a));
    const n = rows.length;
    root.querySelector('#jw-n-filter').textContent = n === all.length ? `ทั้งหมด ${n} คน` : `แสดง ${n} จาก ${all.length} คน`;

    // KPI
    const overall = avg(rows.map(r => +r.overall));
    const kpi = {
      n: n.toLocaleString('th-TH'),
      overall: fmt(overall), overallLv: LEVEL(overall),
      repeat: fmt(pct(rows.filter(r => r.repeat === 'กลับมาแน่นอน').length, n), 0) + '%',
      online: fmt(pct(rows.filter(r => r.online !== 'ไม่ใช้').length, n), 0) + '%',
      gender: `ชาย ${rows.filter(r => r.gender === 'ชาย').length} · หญิง ${rows.filter(r => r.gender === 'หญิง').length}`,
    };
    for (const [k, v] of Object.entries(kpi)) { const el = root.querySelector(`[data-kpi="${k}"]`); if (el) el.textContent = v; }

    // ส่วนที่ 1
    const P1 = Object.fromEntries(S.p1.map(([k, l, o]) => [k, o]));
    draw('jw-c-gender', donutCfg(P1.gender, countBy(rows, 'gender', P1.gender)));
    draw('jw-c-age', barCfg(P1.age, countBy(rows, 'age', P1.age), { total: n }));
    draw('jw-c-occupation', barCfg(P1.occupation, countBy(rows, 'occupation', P1.occupation), { horizontal: true, total: n, color: C.series[2] }));
    draw('jw-c-income', barCfg(P1.income, countBy(rows, 'income', P1.income), { total: n, color: C.series[1] }));
    draw('jw-c-area', donutCfg(P1.area, countBy(rows, 'area', P1.area)));

    // ส่วนที่ 2
    const P2 = Object.fromEntries(S.p2.map(([k, l, o]) => [k, o]));
    const horiz = ['occasion', 'product', 'know', 'purpose', 'factor'];
    S.p2.forEach(([k, l, o], i) => {
      const id = 'jw-c-' + k; if (!document.getElementById(id)) return;
      if (['with', 'pay', 'online', 'repeat'].includes(k)) draw(id, donutCfg(o, countBy(rows, k, o)));
      else draw(id, barCfg(o, countBy(rows, k, o), { horizontal: horiz.includes(k), total: n, color: C.series[i % 3] }));
    });

    // ส่วนที่ 3
    const aspectNames = S.p3.map(([k, name]) => name);
    const aspectAvg = S.p3.map(([k, , items]) => avg(rows.flatMap(r => items.map((_, j) => +r[`${k}_${j + 1}`]))));
    draw('jw-c-aspect', barCfg(aspectNames, aspectAvg.map(x => +x.toFixed(2)), { horizontal: true, max: 5, color: aspectAvg.map(x => x >= 4.21 ? C.series[0] : x >= 3.41 ? C.series[1] : C.series[2]) }));
    const rank1 = S.p3.map(([k]) => rows.filter(r => r[`rank_${k}`] === '1').length);
    draw('jw-c-rank', barCfg(aspectNames, rank1, { total: n, color: C.series[4] }));
    // สัดส่วนคะแนน 1-5 ต่อด้าน
    const levels = [5, 4, 3, 2, 1], lvName = { 5: 'มากที่สุด', 4: 'มาก', 3: 'ปานกลาง', 2: 'น้อย', 1: 'น้อยที่สุด' };
    const stackColors = ['#8E2A1B', '#C2552B', '#D9A441', '#C9BFA8', '#8C8273'];
    draw('jw-c-stack', {
      type: 'bar',
      data: {
        labels: aspectNames,
        datasets: levels.map((lv, i) => ({
          label: `${lv} ${lvName[lv]}`, backgroundColor: stackColors[i],
          data: S.p3.map(([k, , items]) => { const all = rows.flatMap(r => items.map((_, j) => +r[`${k}_${j + 1}`])); return +pct(all.filter(v => v === lv).length, all.length).toFixed(1); }),
        })),
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${c.raw}%` } } },
        scales: { x: { stacked: true, max: 100, grid: { color: C.line }, ticks: { callback: v => v + '%' } }, y: { stacked: true, grid: { display: false } } },
      },
    });
    // ตารางรายข้อ
    let html = '<table class="jw-table"><thead><tr><th>รายการ</th><th class="num">ค่าเฉลี่ย</th><th class="num">S.D.</th><th>ระดับ</th></tr></thead><tbody>';
    S.p3.forEach(([k, name, items]) => {
      const all = rows.flatMap(r => items.map((_, j) => +r[`${k}_${j + 1}`]));
      html += `<tr class="jw-row-aspect"><th>${name}</th><td class="num">${fmt(avg(all))}</td><td class="num">${fmt(sd(all))}</td><td>${LEVEL(avg(all))}</td></tr>`;
      items.forEach((it, j) => {
        const v = rows.map(r => +r[`${k}_${j + 1}`]);
        html += `<tr><td>${it}</td><td class="num">${fmt(avg(v))}</td><td class="num">${fmt(sd(v))}</td><td><span class="jw-meter" style="--v:${(avg(v) - 1) / 4}"></span>${LEVEL(avg(v))}</td></tr>`;
      });
    });
    root.querySelector('#jw-t-sat').innerHTML = html + '</tbody></table>';

    // สรุปอัตโนมัติ
    const best = aspectAvg.indexOf(Math.max(...aspectAvg)), worst = aspectAvg.indexOf(Math.min(...aspectAvg));
    const topProduct = P2.product[countBy(rows, 'product', P2.product).reduce((bi, v, i, arr) => v > arr[bi] ? i : bi, 0)];
    const topFreq = P2.freq[countBy(rows, 'freq', P2.freq).reduce((bi, v, i, arr) => v > arr[bi] ? i : bi, 0)];
    root.querySelector('#jw-summary').innerHTML = n ? `
      <li>ความพึงพอใจภาพรวมต่อร้านเฉลี่ย <strong>${fmt(overall)}</strong> จาก 5 อยู่ในระดับ <strong>${LEVEL(overall)}</strong></li>
      <li>ด้านที่ลูกค้าพึงพอใจสูงสุดคือ <strong>${aspectNames[best]}</strong> (${fmt(aspectAvg[best])}) ส่วนด้านที่ควรพัฒนาคือ <strong>${aspectNames[worst]}</strong> (${fmt(aspectAvg[worst])})</li>
      <li>สินค้าที่ลูกค้าซื้อมากที่สุดคือ <strong>${topProduct}</strong> และส่วนใหญ่มาซื้อ <strong>${topFreq}</strong></li>
      <li>ลูกค้า <strong>${kpi.online}</strong> สนใจสั่งซื้อออนไลน์ถ้าร้านเปิดช่องทาง และ <strong>${kpi.repeat}</strong> ตั้งใจกลับมาซื้อซ้ำแน่นอน</li>` : '<li>ไม่มีข้อมูลตามตัวกรองที่เลือก</li>';

    // ข้อเสนอแนะ
    const sugg = {};
    rows.forEach(r => { const s = (r.suggestion || '').trim(); if (s) sugg[s] = (sugg[s] || 0) + 1; });
    const list = Object.entries(sugg).sort((x, y) => y[1] - x[1]);
    root.querySelector('#jw-sugg').innerHTML = list.length
      ? list.map(([s, c]) => `<li><span>${s.replace(/</g, '&lt;')}</span><b>${c} คน</b></li>`).join('')
      : '<li><span>ยังไม่มีข้อเสนอแนะ</span></li>';
  }

  Promise.all([
    fetch(root.dataset.csv).then(r => { if (!r.ok) throw new Error('csv'); return r.text(); }),
    fetch(root.dataset.schema).then(r => r.json()),
  ]).then(([text, S]) => {
    const all = parseCSV(text);
    const age = root.querySelector('#jw-f-age');
    S.p1.find(x => x[0] === 'age')[2].forEach(o => age.insertAdjacentHTML('beforeend', `<option>${o}</option>`));
    root.querySelectorAll('select').forEach(s => s.addEventListener('change', () => render(all, S)));
    root.querySelector('#jw-f-reset').addEventListener('click', () => { root.querySelectorAll('select').forEach(s => s.value = ''); render(all, S); });
    root.classList.add('is-ready');
    render(all, S);
  }).catch(() => {
    root.querySelector('.jw-dash-body').innerHTML = '<p class="jw-dash-error">โหลดข้อมูลแบบสอบถามไม่สำเร็จ ตรวจว่ามีไฟล์ survey CSV อยู่ในโฟลเดอร์ assets ของปลั๊กอิน แล้วรีเฟรชหน้านี้</p>';
  });
})();
