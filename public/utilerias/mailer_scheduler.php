<?php
// public/utilerias/mailer_scheduler.php

// AJUSTA estas rutas según tu estructura
require_once __DIR__ . '/../../app/mailer_common.php';

global $pdo; // viene de db.php

echo "[" . date('Y-m-d H:i:s') . "] Mailer Scheduler iniciado\n";

// 1) Traer jobs activos listos para ejecutar
$sql = "SELECT * 
        FROM t_correo_job
        WHERE activo = 1
          AND (
                proxima_ejecucion IS NULL
                OR proxima_ejecucion <= NOW()
              )";

$stmtJobs = $pdo->query($sql);
$jobs     = $stmtJobs->fetchAll(PDO::FETCH_ASSOC);

if (!$jobs) {
    echo "No hay jobs pendientes de ejecución.\n";
    exit;
}

// Preparar statement de inserción a queue (se reutiliza)
$sqlInsertQueue = "INSERT INTO t_correo_queue (
    job_id,
    destino_tipo,
    destino_id,
    email_to,
    asunto_resuelto,
    cuerpo_resuelto_html,
    cuerpo_resuelto_texto
) VALUES (
    :job_id,
    :destino_tipo,
    :destino_id,
    :email_to,
    :asunto,
    :html,
    :texto
)";
$stmtInsert = $pdo->prepare($sqlInsertQueue);

foreach ($jobs as $job) {
    $jobId = (int)$job['id'];
    echo "Procesando job #{$jobId} - {$job['nombre']}\n";

    // 2) Obtener plantilla
    $stmtTpl = $pdo->prepare("SELECT * FROM c_correo_plantilla WHERE id = :id AND activo = 1");
    $stmtTpl->execute([':id' => (int)$job['plantilla_id']]);
    $tpl = $stmtTpl->fetch(PDO::FETCH_ASSOC);

    if (!$tpl) {
        echo "  > Plantilla {$job['plantilla_id']} no encontrada o inactiva. Se omite job.\n";
        // aun así programamos siguiente ejecución
        $next = mailer_calcular_proxima_ejecucion($job);
        $sqlUpd = "UPDATE t_correo_job
                   SET ultima_ejecucion = NOW(), proxima_ejecucion = :next
                   WHERE id = :id";
        $stmtUpd = $pdo->prepare($sqlUpd);
        $stmtUpd->execute([
            ':id'   => $jobId,
            ':next' => $next
        ]);
        continue;
    }

    // 3) Obtener destinatarios según tipo_destino
    $destinatarios = mailer_get_destinatarios($pdo, $job);

    if (!$destinatarios) {
        echo "  > No se encontraron destinatarios para este job.\n";
    } else {
        echo "  > Destinatarios encontrados: " . count($destinatarios) . "\n";

        // 4) Insertar en t_correo_queue
        foreach ($destinatarios as $dest) {
            $email = trim($dest['email_to']);
            if ($email === '') {
                continue;
            }

            // Render asunto y cuerpo con variables {CAMPO}
            $asunto = mailer_render_template($tpl['asunto'],        $dest);
            $html   = mailer_render_template($tpl['cuerpo_html'],   $dest);
            $texto  = mailer_render_template($tpl['cuerpo_texto'],  $dest);

            $stmtInsert->execute([
                ':job_id'       => $jobId,
                ':destino_tipo' => $job['tipo_destino'],
                ':destino_id'   => isset($dest['destino_id']) ? (int)$dest['destino_id'] : null,
                ':email_to'     => $email,
                ':asunto'       => $asunto,
                ':html'         => $html,
                ':texto'        => $texto
            ]);
        }
    }

    // 5) Actualizar próxima ejecución
    $next = mailer_calcular_proxima_ejecucion($job);

    $sqlUpd = "UPDATE t_correo_job
               SET ultima_ejecucion = NOW(), proxima_ejecucion = :next
               WHERE id = :id";

    $stmtUpd = $pdo->prepare($sqlUpd);
    $stmtUpd->execute([
        ':id'   => $jobId,
        ':next' => $next
    ]);

    echo "  > Próxima ejecución: " . ($next ?: 'NULL (ON_DEMAND)') . "\n";
}

echo "Scheduler terminado.\n";
