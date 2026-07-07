<?php $__env->startSection('title', 'خريطة الشعب والتخصصات | Cartographie des Spécialités'); ?>

<?php $__env->startSection('styles'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
/* ═══════════════════════════════════════════════════
   CARTOGRAPHIE — Beautiful Light & Platform Theme
═══════════════════════════════════════════════════ */
.carto-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border, #e8edf5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

#algeria-carto-map {
    width: 100%;
    height: 550px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid var(--border, #e8edf5);
    z-index: 1;
}

.carto-hero {
    background: linear-gradient(135deg, #482b8f 0%, #643edb 100%);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    color: #fff;
}
.carto-hero-title {
    font-size: 1.4rem;
    font-weight: 800;
    font-family: 'Cairo', sans-serif;
}
.carto-hero-sub {
    font-size: 0.85rem;
    opacity: 0.85;
    font-family: 'Cairo', sans-serif;
}

/* Year pills */
.yr-tabs {
    display: inline-flex;
    gap: 4px;
    background: rgba(0,0,0,0.04);
    padding: 3px;
    border-radius: 20px;
    border: 1px solid var(--border, #e8edf5);
}
.yr-btn {
    padding: 4px 14px;
    border-radius: 16px;
    border: none;
    background: none;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
    font-family: 'Outfit', sans-serif;
}
.yr-btn:hover {
    color: #643edb;
}
.yr-btn.on {
    background: #643edb;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(100, 62, 219, 0.25);
}

.carto-card.has-flex {
    display: flex;
    flex-direction: column;
    height: calc(100% - 1.5rem);
}

/* Details Box */
.details-box {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid var(--border, #e8edf5);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}

.kpi-mini-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 1rem;
    flex-shrink: 0;
}
.kpi-mini-card {
    background: #fff;
    border: 1px solid var(--border, #e8edf5);
    border-radius: 10px;
    padding: 10px 6px;
    text-align: center;
}
.kpi-mini-val {
    font-size: 1.3rem;
    font-weight: 800;
    color: #643edb;
    font-family: 'Outfit', sans-serif;
}
.kpi-mini-lbl {
    font-size: 0.6rem;
    color: #64748b;
    font-weight: 700;
    margin-top: 2px;
}

/* Branch & Spec list scrolling */
.scroll-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 62, 219, 0.15) transparent;
}
.scroll-list::-webkit-scrollbar { width: 3px; }
.scroll-list::-webkit-scrollbar-thumb { background: rgba(100, 62, 219, 0.2); border-radius: 3px; }

/* Top Specialties Progress Bars */
.sp-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}
.sp-track {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.sp-fill {
    height: 100%;
    background: linear-gradient(90deg, #a5b4fc, #643edb);
    border-radius: 10px;
    transition: width 0.8s ease;
}

.item-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 4px;
    border-bottom: 1px solid rgba(0,0,0,0.02);
}
.item-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #643edb;
    flex-shrink: 0;
}
.item-txt {
    font-size: 0.72rem;
    color: #334155;
    flex: 1;
    font-weight: 600;
}
.item-badge {
    font-size: 0.65rem;
    font-weight: 800;
    color: #643edb;
    background: rgba(100, 62, 219, 0.08);
    padding: 1px 6px;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
}

/* Tooltip Custom styling */
.alg-tooltip.leaflet-tooltip {
    background: rgba(255, 255, 255, 0.98) !important;
    border: 1px solid rgba(100, 62, 219, 0.15) !important;
    border-radius: 10px !important;
    box-shadow: 0 8px 24px rgba(15,23,42,0.06) !important;
    padding: 8px 12px !important;
    color: #0f172a !important;
    font-family: 'Cairo', sans-serif !important;
    pointer-events: none !important;
}
.alg-tooltip.leaflet-tooltip::before { display: none !important; }

/* Custom Legend inside map container */
.map-legend {
    background: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    font-family: 'Cairo', sans-serif;
    font-size: 0.68rem;
    line-height: 1.5;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}
.legend-item:last-child {
    margin-bottom: 0;
}
.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-4 py-2">

    
    <div class="carto-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="carto-hero-title"><i class="fa-solid fa-map-location-dot me-2"></i>الخارطة التفاعلية لتوزيع الشعب والتخصصات المفتوحة</div>
            <div class="carto-hero-sub">عرض مباشر لمستويات انتشار وتوزيع التكوينات المهنية عبر جميع ولايات الجزائر</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="yr-tabs">
                <button class="yr-btn on" data-yr="all"  onclick="setYear('all')">الكل</button>
                <button class="yr-btn"    data-yr="2024" onclick="setYear('2024')">2024</button>
                <button class="yr-btn"    data-yr="2025" onclick="setYear('2025')">2025</button>
                <button class="yr-btn"    data-yr="2026" onclick="setYear('2026')">2026</button>
            </div>
            <a href="/dashboard/specialites" class="btn btn-light btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.78rem; color: #643edb;">
                <i class="fa-solid fa-arrow-right"></i> رجوع
            </a>
        </div>
    </div>

    
    <div class="row g-4">
        
        
        <div class="col-lg-8">
            <div class="carto-card">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #643edb;padding-right:0.6rem;color:#0f172a;">
                    <i class="fa-solid fa-earth-africa text-primary me-2"></i>خريطة توزيع التكوينات والولايات النشطة
                </h6>
                <div id="algeria-carto-map"></div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="carto-card has-flex">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:0.6rem;color:#0f172a;">
                    <i class="fa-solid fa-circle-info text-success me-2"></i>تفاصيل الولاية المحددة
                </h6>
                
                
                <div id="wilaya-detail-container" class="details-box">
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-map-pin fs-3 mb-3 text-secondary"></i>
                        <p class="mb-0 small fw-bold">مرر مؤشر الفأرة فوق أي ولاية في الخارطة أو انقر عليها لعرض التحليلات والتخصصات المفتوحة فوراً.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    
    <div class="row g-4 mt-1">
        
        
        <div class="col-md-6">
            <div class="carto-card">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #a78bfa;padding-right:0.6rem;color:#0f172a;">
                    <i class="fa-solid fa-chart-pie me-2"></i>التوزيع النسبي للشعب المهنية
                </h6>
                <div style="max-height: 220px; position:relative; display: flex; justify-content: center;">
                    <canvas id="donut"></canvas>
                </div>
            </div>
        </div>

        
        <div class="col-md-6">
            <div class="carto-card">
                <h6 class="fw-bold mb-3" style="font-family:'Cairo';border-right:4px solid #f59e0b;padding-right:0.6rem;color:#0f172a;">
                    <i class="fa-solid fa-ranking-star me-2"></i>التخصصات الأكثر طلباً وعروض التكوين المفتوحة
                </h6>
                <?php
                    $mx = max(array_column($topSpecialites, 'cnt') ?: [1]);
                ?>
                <div class="sp-list">
                    <?php $__currentLoopData = $topSpecialites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ts): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-2">
                        <div class="sp-item-row">
                            <span class="sp-name" style="font-size:0.75rem; font-weight:600; color:#334155;"><?php echo e(mb_strlen($ts['spec_ar'])>35?mb_substr($ts['spec_ar'],0,35).'…':$ts['spec_ar']); ?></span>
                            <span class="sp-cnt" style="font-size:0.72rem; font-weight:800; color:#643edb;"><?php echo e($ts['cnt']); ?> عرض</span>
                        </div>
                        <div class="sp-track"><div class="sp-fill" style="width:<?php echo e(round(($ts['cnt']/$mx)*100)); ?>%"></div></div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
window.__D__ = {
    wStats:      <?php echo json_encode($wilayaStats, 15, 512) ?>,
    specialites: <?php echo json_encode($specialites, 15, 512) ?>,
    filieres:    <?php echo json_encode($filieres, 15, 512) ?>,
    offres:      [],
    stats:       <?php echo json_encode($statsOffres, 15, 512) ?>,
    geoJsonData: null,
};
window.__CLR__ = ['#643edb','#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#6366f1','#84cc16','#f97316','#14b8a6','#a855f7','#fb7185','#4ade80','#fbbf24'];
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
'use strict';

/* ── Algeria strict bounds ─────────────────────────────────────────────── */
const ALG_SW     = L.latLng([18.0, -9.0]);
const ALG_NE     = L.latLng([38.0, 13.0]);
const ALG_BOUNDS = L.latLngBounds(ALG_SW, ALG_NE);

/* ── Map — No background tiles to show ONLY Algeria ───────────────────── */
const map = L.map('algeria-carto-map', {
    center:  [28.5, 2.5],
    zoom:    5,
    minZoom: 5,
    maxZoom: 8,
    maxBounds: ALG_BOUNDS,
    maxBoundsViscosity: 1.0,
    scrollWheelZoom: false,
    zoomControl: false,
    attributionControl: false
});

L.control.zoom({position:'bottomleft'}).addTo(map);

/* ── Data ────────────────────────────────────────────────────────────── */
const D      = window.__D__;
const wStats = D.wStats      || [];
const specs  = D.specialites || [];
const fils   = D.filieres    || [];
const offres = D.offres      || [];
const geoData = D.geoJsonData || null;

// Calculate max value for density coloring
function getMaxVal(yr) {
    const counts = wStats.map(ws => {
        if (yr === 'all') return parseInt(ws.nb_spec || 0);
        return parseInt((ws.specs_by_year || {})[yr] || 0);
    });
    return counts.length > 0 ? Math.max(...counts) : 1;
}

let activeYr = 'all';
let maxVal = getMaxVal('all');

function getColor(count) {
    const pct = count / maxVal;
    if (pct > 0.8)  return '#3b228f'; // Deep purple/blue
    if (pct > 0.55) return '#4f46e5';
    if (pct > 0.35) return '#6366f1';
    if (pct > 0.15) return '#818cf8';
    if (pct > 0.02) return '#a5b4fc';
    return '#f1f5f9'; // Default light gray for very low/empty
}

function getFeatureDetails(feature, yr) {
    const wId = parseInt(feature.properties.id || feature.properties.ID || feature.properties.code || 0);
    const ws  = wStats.find(item => parseInt(item.wilaya_id) === wId);
    let count = 0;
    if (ws) {
        count = yr === 'all' ? parseInt(ws.nb_spec || 0) : parseInt((ws.specs_by_year || {})[yr] || 0);
    }
    const name = ws ? ws.wilaya_nom : (feature.properties.name_ar || feature.properties.name || 'ولاية');
    return { wId, ws, count, name };
}

function featureStyle(feature) {
    const { count } = getFeatureDetails(feature, activeYr);
    return {
        fillColor   : getColor(count),
        weight      : 1.2,
        opacity     : 1,
        color       : '#cbd5e1', // Grey borders
        fillOpacity : 0.9
    };
}

let geojsonLayer = null;
let selectedLayer = null;

function updateWilayaPanel(feature) {
    const { ws, name, count } = getFeatureDetails(feature, activeYr);
    const detailBox = document.getElementById('wilaya-detail-container');
    if (!detailBox) return;

    if (!ws) {
        detailBox.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-map-pin fs-3 mb-3 text-secondary"></i>
                <h6 class="fw-bold text-dark">${name}</h6>
                <p class="mb-0 small">لا توجد عروض تكوين مفتوحة أو بيانات مسجلة لهذه الولاية في الفترة المحددة.</p>
            </div>
        `;
        return;
    }

    const pct = maxVal > 0 ? Math.round((count / maxVal) * 100) : 0;
    const topSpecsHtml = (ws.top_specs || []).slice(0, 8).map(s => `
        <div class="item-row">
            <span class="item-dot"></span>
            <span class="item-txt">${s}</span>
        </div>
    `).join('');

    detailBox.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-location-dot text-primary fs-5"></i>
                <h5 class="fw-bold mb-0 text-dark" style="font-family:'Cairo';">${name}</h5>
            </div>
            <span class="badge bg-primary text-white py-1 px-3 fs-7" style="font-family:'Outfit';">رمز الولاية: ${ws.wilaya_id}</span>
        </div>

        <div class="kpi-mini-grid">
            <div class="kpi-mini-card">
                <div class="kpi-mini-val">${ws.nb_etab || 0}</div>
                <div class="kpi-mini-lbl">مؤسسة تكوينية</div>
            </div>
            <div class="kpi-mini-card">
                <div class="kpi-mini-val">${count}</div>
                <div class="kpi-mini-lbl">تخصص مفتوح</div>
            </div>
            <div class="kpi-mini-card" style="grid-column: span 2;">
                <div class="kpi-mini-val" style="color: #10b981;">${(ws.nb_offres || 0).toLocaleString('ar-DZ')}</div>
                <div class="kpi-mini-lbl">عرض تكوين متوفر</div>
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>نسبة الكثافة مقارنة بالولاية الأعلى</span>
                <span class="fw-bold text-primary">${pct}%</span>
            </div>
            <div style="height:6px; border-radius:20px; background:#e2e8f0; overflow:hidden;">
                <div style="height:100%; width:${pct}%; background: linear-gradient(90deg, #a5b4fc, #643edb); border-radius:20px; transition:width 0.8s;"></div>
            </div>
        </div>

        ${topSpecsHtml ? `
            <div class="mt-3">
                <h7 class="fw-bold text-dark d-block mb-2" style="font-size:0.75rem;"><i class="fa-solid fa-graduation-cap text-warning me-1"></i>أبرز التخصصات بالولاية</h7>
                <div class="scroll-list">${topSpecsHtml}</div>
            </div>
        ` : ''}
    `;
}

function onEachFeature(feature, layer) {
    layer.on({
        mouseover(e) {
            const { count, name } = getFeatureDetails(feature, activeYr);
            layer.setStyle({
                weight:      2.2,
                color:       '#643edb',
                fillOpacity: 0.95
            });
            layer.bringToFront();
            layer.bindTooltip(
                `<div style="direction:rtl;text-align:right;">
                    <b style="font-size:12px;color:#0f172a;">${name}</b><br>
                    <span style="color:#643edb;font-size:11px;font-weight:700;">📍 ${count} تخصص</span>
                 </div>`,
                { sticky: true, className: 'alg-tooltip' }
            ).openTooltip();
        },
        mouseout(e) {
            if (selectedLayer !== layer) {
                geojsonLayer.resetStyle(layer);
            }
            layer.closeTooltip();
        },
        click(e) {
            if (selectedLayer) {
                geojsonLayer.resetStyle(selectedLayer);
            }
            selectedLayer = layer;
            layer.setStyle({
                weight:      2.8,
                color:       '#643edb',
                fillOpacity: 1
            });
            layer.bringToFront();
            
            updateWilayaPanel(feature);
            map.fitBounds(layer.getBounds(), { padding: [40, 40] });
        }
    });
}

function drawMap(yr) {
    activeYr = yr;
    maxVal = getMaxVal(yr);
    if (geojsonLayer) {
        geojsonLayer.eachLayer(layer => {
            const { count } = getFeatureDetails(layer.feature, yr);
            layer.setStyle({
                fillColor: getColor(count)
            });
        });
    }
}

/* ── Legend control creation ─────────────────────────────────────────── */
const LegendControl = L.Control.extend({
    options: { position: 'bottomright' },
    onAdd: function (map) {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
            <div style="font-weight:bold;margin-bottom:6px;font-size:0.72rem;">تدرج الكثافة التكوينية</div>
            <div class="legend-item"><div class="legend-color" style="background:#3b228f;"></div><span>مرتفعة جداً</span></div>
            <div class="legend-item"><div class="legend-color" style="background:#4f46e5;"></div><span>مرتفعة</span></div>
            <div class="legend-item"><div class="legend-color" style="background:#6366f1;"></div><span>متوسطة</span></div>
            <div class="legend-item"><div class="legend-color" style="background:#818cf8;"></div><span>مقبولة</span></div>
            <div class="legend-item"><div class="legend-color" style="background:#a5b4fc;"></div><span>قليلة</span></div>
            <div class="legend-item"><div class="legend-color" style="background:#f1f5f9;border:1px solid #cbd5e1;"></div><span>نادرة / منعدمة</span></div>
        `;
        return div;
    }
});
map.addControl(new LegendControl());

/* ── Year filter ─────────────────────────────────────────────────────── */
window.setYear = function(yr) {
    document.querySelectorAll('.yr-btn').forEach(b => b.classList.toggle('on', b.dataset.yr === yr));
    drawMap(yr);
    if (selectedLayer) {
        updateWilayaPanel(selectedLayer.feature);
    }
};

/* ── Donut chart ──────────────────────────────────────────────────────── */
const sbb={};
specs.forEach(s=>{ const b=s.filiere_id||0; sbb[b]=(sbb[b]||0)+1; });
const dL=[],dD=[],dC=[];
fils.forEach((f,i)=>{ const c=sbb[f.id]||0; if(c>0){ dL.push((f.libelle_ar||f.libelle_fr||'—').substring(0,22)); dD.push(c); dC.push(window.__CLR__[i%window.__CLR__.length]); } });
const dEl=document.getElementById('donut');
if(dEl) new Chart(dEl.getContext('2d'),{
    type:'doughnut',
    data:{labels:dL,datasets:[{data:dD,backgroundColor:dC,borderWidth:1.5,borderColor:'#fff',hoverOffset:5}]},
    options:{responsive:true,maintainAspectRatio:true,cutout:'60%',
        plugins:{legend:{display:false},tooltip:{
            backgroundColor:'rgba(255,255,255,0.98)',borderColor:'rgba(100, 62, 219, 0.15)',borderWidth:1,
            titleColor:'#0f172a',bodyColor:'#64748b',padding:11,cornerRadius:12,
            callbacks:{label:c=>` ${c.label}: ${c.parsed} تخصص`}
        }}
    }
});

/* ── Search ───────────────────────────────────────────────────────────── */
const qin=document.getElementById('qin'), drop=document.getElementById('srch-drop');
qin&&qin.addEventListener('input',function(){
    const q=this.value.trim().toLowerCase();
    if(q.length<2){drop.style.display='none';return;}
    const hits=[];
    wStats.forEach(ws=>{
        if((ws.wilaya_nom||'').toLowerCase().includes(q)||(ws.wilaya_fr||'').toLowerCase().includes(q))
            hits.push({t:'w',label:`${ws.wilaya_nom} — ${ws.wilaya_fr}`,wid:ws.wilaya_id});
    });
    specs.forEach(sp=>{
        if((sp.libelle_ar||'').toLowerCase().includes(q)||(sp.libelle_fr||'').toLowerCase().includes(q))
            hits.push({t:'s',label:`${sp.libelle_ar||''} / ${sp.libelle_fr||''}`});
    });
    if(!hits.length){
        drop.innerHTML='<div class="sr-item" style="pointer-events:none;color:rgba(0,0,0,0.22);">لا توجد نتائج</div>';
    } else {
        drop.innerHTML=hits.slice(0,8).map((h,i)=>
            `<div class="sr-item" data-i="${i}"><i class="fa-solid fa-${h.t==='w'?'location-dot':'graduation-cap'}"></i>${h.label}</div>`
        ).join('');
        drop.querySelectorAll('[data-i]').forEach(el=>{
            el.addEventListener('click',()=>{
                const h=hits[parseInt(el.dataset.i)];
                if(h.t==='w'){
                    const targetId = parseInt(h.wid);
                    if (geojsonLayer) {
                        geojsonLayer.eachLayer(layer => {
                            const { wId } = getFeatureDetails(layer.feature, activeYr);
                            if (wId === targetId) {
                                map.fitBounds(layer.getBounds(), { padding: [40, 40] });
                                setTimeout(() => {
                                    layer.fire('click');
                                }, 400);
                            }
                        });
                    }
                }
                drop.style.display='none'; qin.value='';
            });
        });
    }
    drop.style.display='block';
});
document.addEventListener('click',e=>{ if(!e.target.closest('#qin')&&!e.target.closest('#srch-drop')) drop.style.display='none'; });

/* ── Init ─────────────────────────────────────────────────────────────── */
map.whenReady(()=>{
    fetch('/algeria-wilayas-simple.geojson')
        .then(response => response.json())
        .then(geoJson => {
            geojsonLayer = L.geoJSON(geoJson, {
                style: featureStyle,
                onEachFeature: onEachFeature
            }).addTo(map);
            
            const ov=document.getElementById('ov');
            if(ov){ ov.style.opacity='0'; setTimeout(()=>ov.style.display='none',500); }
        })
        .catch(err => {
            console.error("Failed to load Algeria GeoJSON map:", err);
            const ov=document.getElementById('ov');
            if(ov){ ov.style.opacity='0'; setTimeout(()=>ov.style.display='none',500); }
        });
    
    map.fitBounds([[19.0,-8.7],[37.15,12.0]], {paddingTopLeft:[20,40], paddingBottomRight:[20,40]});
});

})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/admin/specialites/cartographie.blade.php ENDPATH**/ ?>