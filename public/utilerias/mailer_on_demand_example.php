<?php
// public/utilerias/mailer_on_demand_example.php

require_once __DIR__ . '/../../app/mailer_common.php';

global $pdo;

// EJEMPLO: mandar un correo manual a un cliente específico (id = 123)
$idCliente = 123;

// Traer datos de cliente para armar cuerpo
$stmt = $pdo->prepare("SELECT 
                           id          AS destino_id,
                           RazonSocial AS nombre,
                           Correo      AS email_to,
                           '1000.00'   AS saldo,
                           DATE_FORMAT(NOW(), '%d/%m/%Y') AS fecha
                       FROM c_cliente
                       WHERE id = :id
                         AND Correo IS NOT NULL
                         AND Correo <> ''");
$stmt->execute([':id' => $idCliente]);
$cli = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cli) {
    echo "Cliente no encontrado o sin correo.\n";
    exit;
}

// Texto ejemplo con campos {nombre}, {saldo}, {fecha}
$asuntoTpl = 'Estado de cuenta {nombre}';
$htmlTpl   = '<p>Estimado(a) {nombre},</p>
<p>Su saldo actual es de <strong>{saldo}</strong> al {fecha}.</p>
<p>Saludos.</p>';

$asunto = mailer_render_template($asuntoTpl, $cli);
$html   = mailer_render_template($htmlTpl, $cli);

// Insertar directo en t_correo_queue
$sql = "INSERT INTO t_correo_queue (
            job_id,
            destino_tipo,
            destino_id,
            email_to,
            asunto_resuelto,
            cuerpo_resuelto_html,
            cuerpo_resuelto_texto
        ) VALUES (
            NULL,
            'CLIENTE',
            :destino_id,
            :email_to,
            :asunto,
            :html,
            NULL
        )";

$stmtIns = $pdo->prepare($sql);
$stmtIns->execute([
    ':destino_id' => $cli['destino_id'],
    ':email_to'   => $cli['email_to'],
    ':asunto'     => $asunto,
    ':html'       => $html
]);

echo "Correo encolado para {$cli['email_to']}. El worker lo enviará.\n";
