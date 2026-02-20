<?php
// public/dashboard/dashboard_lta.php
// Dashboard ejecutivo de visibilidad logística (LTA) — últimos 7 días + alertas >7 días sin evento.
// Snapshot en modal (timeline + tracking) útil para OCs cerradas y TR.

require_once __DIR__ . '/../../app/db.php';

try {
    $pdo = db_pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die('Error de conexión a base de datos: ' . htmlspecialchars($e->getMessage()));
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$DIAS_RIESGO = 7; // regla fija solicitada
$DIAS_FEED   = 7; // ventana actividad solicitada

/* =========================================================
   AJAX: Snapshot OC (caso + eventos + tracking)
   ========================================================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'oc_snapshot') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $idAduana = (int)($_GET['id_aduana'] ?? 0);
        if ($idAduana <= 0) throw new Exception('ID_Aduana inválido');

        // 1) Header OC
        $qOc = $pdo->prepare("
            SELECT
                h.ID_Aduana,
                COALESCE(NULLIF(h.folio_mov,''), NULLIF(h.Pedimento,'')) AS num_oc,
                h.status,
                h.Cve_Almac,
                h.fecha_mov,
                h.fech_pedimento,
                h.fecha_eta,
                h.cve_usuario,
                h.ID_Proveedor,
                p.Nombre AS proveedor
            FROM th_aduana h
            LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
            WHERE h.ID_Aduana = :id
            LIMIT 1
        ");
        $qOc->execute([':id' => $idAduana]);
        $oc = $qOc->fetch(PDO::FETCH_ASSOC);
        if (!$oc) throw new Exception('OC no encontrada');

        // 2) Caso LTA asociado
        $qCase = $pdo->prepare("
            SELECT *
            FROM lta_case
            WHERE tipo='OC' AND id_aduana=:id
            ORDER BY id_lta DESC
            LIMIT 1
        ");
        $qCase->execute([':id' => $idAduana]);
        $case = $qCase->fetch(PDO::FETCH_ASSOC) ?: null;

        $id_lta = (int)($case['id_lta'] ?? 0);

        // 3) Eventos (no se reemplazan, se listan en orden)
        $events = [];
        if ($id_lta > 0) {
            $qEv = $pdo->prepare("
                SELECT id_event, id_lta, evento, fecha_evento, fuente, comentario
                FROM lta_event
                WHERE id_lta = :id_lta
                ORDER BY fecha_evento ASC, id_event ASC
            ");
            $qEv->execute([':id_lta' => $id_lta]);
            $events = $qEv->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4) Tracking (lista)
        $tracking = [];
        if ($id_lta > 0) {
            $qTr = $pdo->prepare("
                SELECT id_tracking, id_lta, tracking_no, carrier, activo
                FROM lta_tracking
                WHERE id_lta = :id_lta
                ORDER BY activo DESC, id_tracking DESC
            ");
            $qTr->execute([':id_lta' => $id_lta]);
            $tracking = $qTr->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'ok' => true,
            'oc' => $oc,
            'case' => $case,
            'events' => $events,
            'tracking' => $tracking,
        ]);
        exit;

    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        exit;
    }
}

/* =========================================================
   KPIs + Feed + Riesgos
   ========================================================= */

$kpi = [
    'oc_tr' => 0,
    'oc_c'  => 0,
    'trk_activo' => 0,
    'eventos_hoy' => 0,
    'riesgo' => 0,
];

try {
    $kpi['oc_tr'] = (int)$pdo->query("SELECT COUNT(*) FROM th_aduana WHERE status='TR'")->fetchColumn();
    $kpi['oc_c']  = (int)$pdo->query("SELECT COUNT(*) FROM th_aduana WHERE status='C'")->fetchColumn();
    $kpi['eventos_hoy'] = (int)$pdo->query("SELECT COUNT(*) FROM lta_event WHERE DATE(fecha_evento)=CURDATE()")->fetchColumn();

    $kpi['trk_activo'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM lta_tracking t
        INNER JOIN lta_case c ON c.id_lta=t.id_lta
        WHERE c.tipo='OC' AND t.activo=1
    ")->fetchColumn();

    // Riesgo: OCs en TR con > 7 días sin evento
    $stRisk = $pdo->prepare("
        SELECT COUNT(*) FROM (
            SELECT
                c.id_lta,
                c.id_aduana,
                MAX(e.fecha_evento) AS last_event
            FROM lta_case c
            LEFT JOIN lta_event e ON e.id_lta=c.id_lta
            INNER JOIN th_aduana h ON h.ID_Aduana=c.id_aduana
            WHERE c.tipo='OC'
              AND h.status='TR'
            GROUP BY c.id_lta, c.id_aduana
            HAVING DATEDIFF(NOW(), COALESCE(MAX(e.fecha_evento), c.updated_at, c.created_at, c.fecha_inicio)) > :dias
        ) x
    ");
    $stRisk->execute([':dias' => $DIAS_RIESGO]);
    $kpi['riesgo'] = (int)$stRisk->fetchColumn();

} catch (Throwable $e) {
    // KPIs quedan en 0 si algo falla
}

/* ---- Feed últimos 7 días ---- */
$feed = [];
try {
    $st = $pdo->prepare("
        SELECT
            e.id_event,
            e.evento,
            e.fecha_evento,
            e.fuente,
            e.comentario,

            c.id_lta,
            c.id_aduana,
            c.transporte,
            c.estado AS estado_lta,

            COALESCE(NULLIF(h.folio_mov,''), NULLIF(h.Pedimento,'')) AS num_oc,
            h.status AS status_oc,
            h.Cve_Almac,
            p.Nombre AS proveedor,

            (
              SELECT GROUP_CONCAT(DISTINCT t.tracking_no ORDER BY t.activo DESC, t.id_tracking DESC SEPARATOR ' | ')
              FROM lta_tracking t
              WHERE t.id_lta = c.id_lta AND t.activo = 1
            ) AS tracking_activo
        FROM lta_event e
        INNER JOIN lta_case c ON c.id_lta = e.id_lta
        LEFT JOIN th_aduana h ON h.ID_Aduana = c.id_aduana
        LEFT JOIN c_proveedores p ON p.ID_Proveedor = h.ID_Proveedor
        WHERE c.tipo='OC'
          AND e.fecha_evento >= (NOW() - INTERVAL :dias DAY)
        ORDER BY e.fecha_evento DESC, e.id_event DESC
        LIMIT 250
    ");
    $st->execute([':dias' => $DIAS_FEED]);
    $feed = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $feed = [];
}

/* ---- Tabla riesgo ---- */
$riesgos = [];
try {
    $st = $pdo->prepare("
        SELECT
            c.id_lta,
            c.id_aduana,
            c.transporte,
            c.estado AS estado_lta,
            MAX(e.fecha_evento) AS last_event,
            COALESCE(NULLIF(h.folio_mov,''), NULLIF(h.Pedimento,'')) AS num_oc,
            h.Cve_Almac,
            h.status AS status_oc,
            p.Nombre AS proveedor,
            DATEDIFF(NOW(), COALESCE(MAX(e.fecha_evento), c.updated_at, c.created_at, c.fecha_inicio)) AS dias_sin_evento
        FROM lta_case c
        LEFT JOIN lta_event e ON e.id_lta=c.id_lta
        INNER JOIN th_aduana h ON h.ID_Aduana=c.id_aduana
        LEFT JOIN c_proveedores p ON p.ID_Proveedor=h.ID_Proveedor
        WHERE c.tipo='OC'
          AND h.status='TR'
        GROUP BY c.id_lta, c.id_aduana, c.transporte, c.estado, num_oc, h.Cve_Almac, h.status, p.Nombre
        HAVING dias_sin_evento > :dias
        ORDER BY dias_sin_evento DESC, last_event ASC
        LIMIT 200
    ");
    $st->execute([':dias' => $DIAS_RIESGO]);
    $riesgos = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $riesgos = [];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard LTA — Visibilidad logística (7 días)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{ background:#f5f7fb; font-family:system-ui,-apple-system,"Segoe UI",sans-serif; font-size:12px; }
        .ap-title{ font-weight:800; color:#0F5AAD; }
        .ap-card{ background:#fff; border:1px solid #eef1f7; border-radius:16px; box-shadow:0 10px 30px rgba(15,90,173,.08); padding:12px; }
        .kpi{ border-radius:16px; padding:12px; border:1px solid #eef1f7; background:#fff; box-shadow:0 8px 24px rgba(15,90,173,.08); }
        .kpi .label{ color:#6b7280; font-size:12px; font-weight:700; }
        .kpi .value{ font-size:24px; font-weight:900; }
        .pill{ border-radius:999px; padding:2px 10px; font-weight:800; font-size:11px; }
        .table thead th{ position:sticky; top:0; background:#f8fafc; z-index:2; }
        .link-oc{ text-decoration:none; font-weight:800; }
        .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
        .smallmuted{ font-size:11px; color:#6b7280; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../bi/_menu_global.php'; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <div class="ap-title">Dashboard LTA — Visibilidad logística</div>
            <div class="smallmuted">
                Ventana actividad: últimos <b><?php echo (int)$DIAS_FEED; ?></b> días · Alerta riesgo: <b>&gt;<?php echo (int)$DIAS_RIESGO; ?></b> días sin evento · Fuente: lta_case / lta_event / lta_tracking / th_aduana
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="../ingresos/orden_compra.php">Ir a Órdenes de Compra</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-2 mb-2">
        <div class="col-md-2 col-sm-6">
            <div class="kpi">
                <div class="label">OCs en Tracking (TR)</div>
                <div class="value"><?php echo (int)$kpi['oc_tr']; ?></div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="kpi">
                <div class="label">OCs Cerradas</div>
                <div class="value"><?php echo (int)$kpi['oc_c']; ?></div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="kpi">
                <div class="label">Tracking activo</div>
                <div class="value"><?php echo (int)$kpi['trk_activo']; ?></div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="kpi">
                <div class="label">Eventos hoy</div>
                <div class="value"><?php echo (int)$kpi['eventos_hoy']; ?></div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12">
            <div class="kpi">
                <div class="label">OCs en riesgo (&gt;<?php echo (int)$DIAS_RIESGO; ?> días sin evento, status TR)</div>
                <div class="value text-danger"><?php echo (int)$kpi['riesgo']; ?></div>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <!-- Feed -->
        <div class="col-lg-7">
            <div class="ap-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">Actividad (últimos <?php echo (int)$DIAS_FEED; ?> días)</div>
                    <div class="smallmuted">Top 250 eventos</div>
                </div>

                <div class="table-responsive" style="max-height:560px; overflow:auto; border-radius:12px; border:1px solid #e5e7eb;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>OC</th>
                                <th>Evento</th>
                                <th>Tracking</th>
                                <th>Transporte</th>
                                <th>Proveedor</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$feed): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Sin eventos en la ventana.</td></tr>
                        <?php else: ?>
                            <?php foreach ($feed as $r):
                                $evt = (string)($r['evento'] ?? '');
                                $badge = 'secondary';
                                if ($evt === 'EMBARQUE') $badge = 'primary';
                                if ($evt === 'INGRESO_ADUANA') $badge = 'warning';
                                if ($evt === 'SALIDA_ADUANA') $badge = 'success';
                            ?>
                            <tr>
                                <td class="mono"><?php echo e((string)($r['fecha_evento'] ?? '')); ?></td>
                                <td>
                                    <a href="#" class="link-oc oc-snap" data-id="<?php echo (int)$r['id_aduana']; ?>">
                                        <?php echo e((string)($r['num_oc'] ?? '')); ?>
                                    </a>
                                    <div class="smallmuted"><?php echo e((string)($r['Cve_Almac'] ?? '')); ?> · OC <?php echo e((string)($r['status_oc'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <span class="pill bg-<?php echo e($badge); ?> text-white"><?php echo e($evt); ?></span>
                                    <div class="smallmuted"><?php echo e((string)($r['fuente'] ?? '')); ?></div>
                                </td>
                                <td class="mono"><?php echo e((string)($r['tracking_activo'] ?? '')); ?></td>
                                <td><?php echo e((string)($r['transporte'] ?? '')); ?></td>
                                <td><?php echo e((string)($r['proveedor'] ?? '')); ?></td>
                                <td style="max-width:360px;">
                                    <div class="text-truncate" title="<?php echo e((string)($r['comentario'] ?? '')); ?>">
                                        <?php echo e((string)($r['comentario'] ?? '')); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riesgos -->
        <div class="col-lg-5">
            <div class="ap-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">OCs en riesgo</div>
                    <div class="smallmuted">&gt;<?php echo (int)$DIAS_RIESGO; ?> días sin evento</div>
                </div>

                <div class="table-responsive" style="max-height:560px; overflow:auto; border-radius:12px; border:1px solid #e5e7eb;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>OC</th>
                                <th>Días</th>
                                <th>Último evento</th>
                                <th>Transporte</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$riesgos): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin riesgos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riesgos as $r):
                                $dias = (int)($r['dias_sin_evento'] ?? 0);
                                $sev = 'success';
                                if ($dias >= ($DIAS_RIESGO+3)) $sev = 'warning';
                                if ($dias >= ($DIAS_RIESGO+7)) $sev = 'danger';
                            ?>
                            <tr>
                                <td>
                                    <a href="#" class="link-oc oc-snap" data-id="<?php echo (int)$r['id_aduana']; ?>">
                                        <?php echo e((string)($r['num_oc'] ?? '')); ?>
                                    </a>
                                    <div class="smallmuted"><?php echo e((string)($r['Cve_Almac'] ?? '')); ?> · TR</div>
                                </td>
                                <td><span class="pill bg-<?php echo e($sev); ?> text-white"><?php echo $dias; ?>d</span></td>
                                <td class="mono"><?php echo e((string)($r['last_event'] ?? '')); ?></td>
                                <td><?php echo e((string)($r['transporte'] ?? '')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="smallmuted mt-2">
                    Regla: se calcula con MAX(lta_event.fecha_evento); si no hay eventos, usa updated_at / created_at / fecha_inicio del caso.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Snapshot OC -->
<div class="modal fade" id="modalSnap" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
            <div class="fw-bold" id="snapTitle">Snapshot OC</div>
            <div class="smallmuted" id="snapSub"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="snapErr" class="text-danger mb-2" style="display:none;"></div>

        <div class="row g-2">
            <div class="col-lg-4">
                <div class="ap-card" style="box-shadow:none;">
                    <div class="fw-bold mb-2">Resumen</div>
                    <div class="smallmuted">OC</div>
                    <div class="mono fw-bold" id="snapOC"></div>

                    <div class="smallmuted mt-2">Status OC</div>
                    <div class="mono" id="snapStatus"></div>

                    <div class="smallmuted mt-2">Almacén</div>
                    <div class="mono" id="snapAlm"></div>

                    <div class="smallmuted mt-2">Proveedor</div>
                    <div id="snapProv"></div>

                    <hr>
                    <div class="fw-bold mb-2">Tracking</div>
                    <div id="snapTracking" class="small"></div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ap-card" style="box-shadow:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">Timeline (eventos)</div>
                        <div class="smallmuted" id="snapCount"></div>
                    </div>

                    <div class="table-responsive" style="max-height:460px; overflow:auto; border-radius:12px; border:1px solid #e5e7eb;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Evento</th>
                                    <th>Fuente</th>
                                    <th>Comentario</th>
                                </tr>
                            </thead>
                            <tbody id="snapEvents">
                                <tr><td colspan="4" class="text-center text-muted py-4">Cargando…</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="smallmuted mt-2">Snapshot: solo lectura (útil también para OCs cerradas).</div>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a class="btn btn-outline-primary" id="snapGoOC" href="#" target="_blank">Abrir OC</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../bi/_menu_global_end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
    const modalEl = document.getElementById('modalSnap');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const $ = (id)=>document.getElementById(id);

    function esc(s){ return (s ?? '').toString(); }

    async function openSnapshot(idAduana){
        if (!modal) return;

        $('snapErr').style.display='none';
        $('snapErr').textContent='';

        $('snapTitle').textContent = 'Snapshot OC';
        $('snapSub').textContent = '';
        $('snapOC').textContent = '';
        $('snapStatus').textContent = '';
        $('snapAlm').textContent = '';
        $('snapProv').textContent = '';
        $('snapTracking').innerHTML = '';
        $('snapCount').textContent = '';

        $('snapEvents').innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Cargando…</td></tr>`;
        $('snapGoOC').href = `../ingresos/orden_compra_edit.php?id_aduana=${encodeURIComponent(idAduana)}`;

        modal.show();

        try{
            const url = new URL(window.location.href);
            url.searchParams.set('ajax','oc_snapshot');
            url.searchParams.set('id_aduana', idAduana);

            const resp = await fetch(url.toString(), {headers:{'Accept':'application/json'}});
            const data = await resp.json();

            if(!data || !data.ok){
                throw new Error(data && data.msg ? data.msg : 'Error al cargar snapshot');
            }

            const oc = data.oc || {};
            const c  = data.case || {};
            const ev = Array.isArray(data.events) ? data.events : [];
            const tr = Array.isArray(data.tracking) ? data.tracking : [];

            $('snapTitle').textContent = `Snapshot OC — ${esc(oc.num_oc || '')}`;
            $('snapSub').textContent = `ID_Aduana: ${esc(oc.ID_Aduana || '')} · Caso LTA: ${esc(c.id_lta || '—')} · Transporte: ${esc(c.transporte || '—')}`;

            $('snapOC').textContent = esc(oc.num_oc || '');
            $('snapStatus').textContent = esc(oc.status || '');
            $('snapAlm').textContent = esc(oc.Cve_Almac || '');
            $('snapProv').textContent = esc(oc.proveedor || '');

            if (tr.length === 0){
                $('snapTracking').innerHTML = `<span class="text-muted">Sin tracking registrado.</span>`;
            } else {
                $('snapTracking').innerHTML = tr.map(x=>{
                    const act = (String(x.activo) === '1') ? '✅' : '⏸️';
                    const carrier = x.carrier ? ` · ${esc(x.carrier)}` : '';
                    return `<div class="mono">${act} ${esc(x.tracking_no || '')}${carrier}</div>`;
                }).join('');
            }

            $('snapCount').textContent = `${ev.length} evento(s)`;

            if (ev.length === 0){
                $('snapEvents').innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">Sin eventos.</td></tr>`;
            } else {
                $('snapEvents').innerHTML = ev.map(x=>{
                    return `
                        <tr>
                            <td class="mono">${esc(x.fecha_evento || '')}</td>
                            <td><span class="badge text-bg-secondary">${esc(x.evento || '')}</span></td>
                            <td class="mono">${esc(x.fuente || '')}</td>
                            <td>${esc(x.comentario || '')}</td>
                        </tr>
                    `;
                }).join('');
            }

        } catch(err){
            $('snapErr').textContent = 'Error: ' + (err && err.message ? err.message : err);
            $('snapErr').style.display = '';
            $('snapEvents').innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No disponible.</td></tr>`;
        }
    }

    document.addEventListener('click', (e)=>{
        const a = e.target.closest('.oc-snap');
        if (!a) return;
        e.preventDefault();
        const id = a.dataset.id;
        if (!id) return;
        openSnapshot(id);
    });

})();
</script>

</body>
</html>
