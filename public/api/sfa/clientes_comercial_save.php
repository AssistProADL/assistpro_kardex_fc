<?php
// public/api/sfa/clientes_comercial_save.php
// Guarda asignación comercial en relclilis.
// POST JSON:
// {
//   "destinatarios": [424,444],
//   "listap": 1,
//   "listapromo": 2,
//   "listad": 3,
//   "mode": "replace" | "only_empty" (default replace)
// }

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../app/db.php';

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

try {
  $body = read_json_body();
  $destinatarios = $body['destinatarios'] ?? [];
  if (!is_array($destinatarios)) $destinatarios = [];
  $destinatarios = array_values(array_unique(array_filter(array_map('intval', $destinatarios))));

  $listap = isset($body['listap']) ? (int)$body['listap'] : 0;
  $listapromo = isset($body['listapromo']) ? (int)$body['listapromo'] : 0;
  $listad = isset($body['listad']) ? (int)$body['listad'] : 0;
  $mode = ($body['mode'] ?? 'replace');
  if (!in_array($mode, ['replace','only_empty'], true)) $mode = 'replace';

  if (!$destinatarios) {
    echo json_encode(['ok'=>0,'error'=>'Destinatarios requeridos']);
    exit;
  }

  // upsert por destinatario
  $ok = 0; $err = 0; $detalle_err = [];

  db_tx(function() use ($destinatarios,$listap,$listapromo,$listad,$mode,&$ok,&$err,&$detalle_err) {
    foreach ($destinatarios as $id_dest) {
      try {
        $row = db_one('SELECT Id_Destinatario, ListaP, ListaPromo, ListaD FROM relclilis WHERE Id_Destinatario = ? LIMIT 1', [$id_dest]);

        if ($row) {
          if ($mode === 'only_empty') {
            $newP = ($row['ListaP'] ? $row['ListaP'] : $listap);
            $newPr = ($row['ListaPromo'] ? $row['ListaPromo'] : $listapromo);
            $newD = ($row['ListaD'] ? $row['ListaD'] : $listad);
          } else {
            $newP = $listap;
            $newPr = $listapromo;
            $newD = $listad;
          }
          dbq('UPDATE relclilis SET ListaP = ?, ListaPromo = ?, ListaD = ? WHERE Id_Destinatario = ?', [$newP, $newPr, $newD, $id_dest]);
        } else {
          dbq('INSERT INTO relclilis (Id_Destinatario, ListaP, ListaPromo, ListaD, Activo) VALUES (?,?,?,?,1)', [$id_dest, $listap, $listapromo, $listad]);
        }
        $ok++;
      } catch (Throwable $e) {
        $err++;
        $detalle_err[] = ['id_destinatario'=>$id_dest,'err'=>$e->getMessage()];
      }
    }
  });

  echo json_encode(['ok'=>1,'total_ok'=>$ok,'total_err'=>$err,'errors'=>$detalle_err]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>0,'error'=>'Error servidor','detalle'=>$e->getMessage()]);
}
