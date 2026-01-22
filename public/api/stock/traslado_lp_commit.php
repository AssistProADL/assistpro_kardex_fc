<?php
// public/api/stock/traslado_lp_commit.php
// Commit Traslado LP: MOVE (sin fusion) / MERGE (fusion a LP destino)
// Escribe en t_cardex (108 Traslado Interno) y audita en t_movcharolas

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../app/db.php';

function respond($payload, int $code = 200): void
{
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function get_json_input(): array
{
  $raw = file_get_contents('php://input');
  if (!$raw)
    return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

try {
  $pdo = db();

  $in = array_merge($_GET ?? [], $_POST ?? [], get_json_input());

  $cvelp_origen = trim((string) ($in['CveLP_origen'] ?? $in['cvelp_origen'] ?? ''));
  $bl_destino = trim((string) ($in['bl_destino'] ?? $in['BL_destino'] ?? $in['destino'] ?? ''));
  $usuario = trim((string) ($in['usuario'] ?? $in['Cve_Usuario'] ?? ''));
  $cvelp_destino = trim((string) ($in['CveLP_destino'] ?? $in['cvelp_destino'] ?? ''));

  if ($cvelp_origen === '' || $bl_destino === '' || $usuario === '') {
    respond(['ok' => 0, 'error' => 'Requeridos: CveLP_origen, bl_destino, usuario.'], 400);
  }

  $modo = ($cvelp_destino !== '') ? 'MERGE' : 'MOVE';
  $tipo_mov = 108; // Traslado Interno

  // Validar BL destino
  $st = $pdo->prepare("SELECT CodigoCSD, AcomodoMixto, Activo FROM c_ubicacion WHERE CodigoCSD = :bl LIMIT 1");
  $st->execute([':bl' => $bl_destino]);
  $ub = $st->fetch(PDO::FETCH_ASSOC);
  if (!$ub) {
    // FALLBACK: ¿Es un LP escaneado en lugar de un BL?
    $stLP = $pdo->prepare("SELECT IDContenedor FROM c_charolas WHERE CveLP = :code LIMIT 1");
    $stLP->execute([':code' => $bl_destino]);
    $lpRow = $stLP->fetch(PDO::FETCH_ASSOC);

    if ($lpRow) {
      // Buscar su ubicación actual
      $stLoc = $pdo->prepare("SELECT bl FROM v_inv_existencia_multinivel WHERE nTarima = :idCont AND cantidad > 0 GROUP BY bl ORDER BY SUM(cantidad) DESC LIMIT 1");
      $stLoc->execute([':idCont' => $lpRow['IDContenedor']]);
      $locVal = $stLoc->fetchColumn();

      if ($locVal) {
        $bl_destino = $locVal; // Corregimos el BL destino internamente
        $st->execute([':bl' => $bl_destino]); // Reintentar lookup de BL
        $ub = $st->fetch(PDO::FETCH_ASSOC);
      }
    }
  }

  if (!$ub)
    respond(['ok' => 0, 'error' => 'BL destino no existe.'], 404);
  if ((int) ($ub['Activo'] ?? 0) !== 1)
    respond(['ok' => 0, 'error' => 'BL destino inactivo.'], 400);
  if (strtoupper((string) ($ub['AcomodoMixto'] ?? 'N')) !== 'S') {
    respond(['ok' => 0, 'error' => 'BL destino no habilitado para Acomodo Mixto.'], 400);
  }

  // Maestro LP origen
  $st = $pdo->prepare("SELECT IDContenedor, cve_almac, tipo, Activo, Clave_Contenedor, CveLP FROM c_charolas WHERE CveLP = :lp LIMIT 1");
  $st->execute([':lp' => $cvelp_origen]);
  $lpO = $st->fetch(PDO::FETCH_ASSOC);
  if (!$lpO)
    respond(['ok' => 0, 'error' => 'LP origen no existe en c_charolas.'], 404);
  $idContO = (int) $lpO['IDContenedor'];
  $tipoO = strtoupper(trim((string) ($lpO['tipo'] ?? '')));
  $almO = (string) ($lpO['cve_almac'] ?? '');

  // Detalle LP origen desde vista (por SKU/Lote)
  $sqlDet = "
    SELECT v.bl, v.cve_articulo, v.cve_lote, SUM(v.cantidad) AS cantidad
    FROM v_inv_existencia_multinivel v
    WHERE v.nTarima = :idCont AND v.cantidad > 0
    GROUP BY v.bl, v.cve_articulo, v.cve_lote
  ";
  $st = $pdo->prepare($sqlDet);
  $st->execute([':idCont' => $idContO]);
  $det = $st->fetchAll(PDO::FETCH_ASSOC);

  // FIX: [Zombie LP] Si el LP tiene Stock físico pero estatus Inactivo (por error previo), permitimos moverlo para corregir.
  if ((int) ($lpO['Activo'] ?? 0) !== 1) {
    if (!$det) {
      respond(['ok' => 0, 'error' => 'LP origen inactivo/bloqueado y sin stock.'], 400);
    }
    // Si tiene stock, permitimos continuar ("Zombie Mode")
  }

  if (!$det)
    respond(['ok' => 0, 'error' => 'LP origen sin existencia positiva.'], 400);

  // Maestro LP destino (solo MERGE)
  $lpD = null;
  $idContD = null;
  if ($modo === 'MERGE') {
    $st = $pdo->prepare("SELECT IDContenedor, cve_almac, tipo, Activo, Clave_Contenedor, CveLP FROM c_charolas WHERE CveLP = :lp LIMIT 1");
    $st->execute([':lp' => $cvelp_destino]);
    $lpD = $st->fetch(PDO::FETCH_ASSOC);
    if (!$lpD)
      respond(['ok' => 0, 'error' => 'LP destino no existe en c_charolas.'], 404);
    if ((int) ($lpD['Activo'] ?? 0) !== 1)
      respond(['ok' => 0, 'error' => 'LP destino inactivo/bloqueado.'], 400);
    $idContD = (int) $lpD['IDContenedor'];

    $tipoD = strtoupper(trim((string) ($lpD['tipo'] ?? '')));
    // Validacion Tipo (Desactivada por solicitud usuario "ESO QUE")
    /*
    if ($tipoO !== '' && $tipoD !== '' && $tipoO !== $tipoD) {
      respond(['ok' => 0, 'error' => 'Tipo LP origen/destino no coincide (Pallet vs Contenedor).'], 400);
    }
    */

    // Validar que LP destino esté en BL destino (si tiene existencia)
    $st = $pdo->prepare("SELECT bl, SUM(cantidad) qty FROM v_inv_existencia_multinivel WHERE nTarima = :id AND cantidad > 0 GROUP BY bl");
    $st->execute([':id' => $idContD]);
    $blDest = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($blDest) {
      $ok = false;
      foreach ($blDest as $r)
        if ($r['bl'] === $bl_destino) {
          $ok = true;
          break;
        }
      if (!$ok)
        respond(['ok' => 0, 'error' => 'LP destino tiene existencia, pero no está en el BL destino seleccionado.'], 400);
    }
  }

  // Detalle LP origen (Ya consultado arriba para validar Zombie Mode)
  // $det ya contiene las filas con cantidad > 0.


  // BL origen único
  $blMap = [];
  foreach ($det as $r)
    $blMap[$r['bl']] = 1;
  $bls = array_keys($blMap);

  // FIX: Permitir múltiples BLs para poder reparar LPs divididos ("Split Location")
  // if (count($bls) !== 1) {
  //   respond(['ok' => 0, 'error' => 'LP origen aparece en múltiples BL. Requiere normalización previa.', 'bls' => $bls], 400);
  // }

  // Usamos el primer BL para registro en Cardex (aunque físicamente mueva todo)
  $bl_origen = $bls[0] ?? '';

  // Folio (simple y operativo)
  // Folio (simple y operativo)
  // FIX: [SQL 1406] Truncate to avoid 'Data too long', assuming limit around 20-30 chars
  // Format: MOV-YmdHis (14 chars) + - + CveLP (variable)
  $folioRaw = 'MOV-' . date('YmdHis') . '-' . $cvelp_origen;
  $folio = substr($folioRaw, 0, 20); // Strict safety cap

  $pdo->beginTransaction();

  // IDs t_cardex / t_movcharolas (MAX+1 con bloqueo)
  $st = $pdo->query("SELECT IFNULL(MAX(id),0) AS mx FROM t_cardex FOR UPDATE");
  $nextCardex = (int) $st->fetchColumn();

  $st = $pdo->query("SELECT IFNULL(MAX(id),0) AS mx FROM t_movcharolas FOR UPDATE");
  $nextMov = (int) $st->fetchColumn();

  $fecha = date('Y-m-d H:i:s');

  $lpDestinoEnCardex = ($modo === 'MERGE') ? $cvelp_destino : $cvelp_origen;
  $claveDestinoEnCardex = ($modo === 'MERGE') ? ($lpD['Clave_Contenedor'] ?? $cvelp_destino) : ($lpO['Clave_Contenedor'] ?? $cvelp_origen);

  // Insert por SKU/Lote
  $last_cardex_id = null;
  $ins = $pdo->prepare("
    INSERT INTO t_cardex
      (id, cve_articulo, cve_lote, fecha, origen, destino, cantidad, ajuste, stockinicial,
       id_TipoMovimiento, cve_usuario, Cve_Almac, Cve_Almac_Origen, Cve_Almac_Destino, Activo,
       Referencia, contenedor_clave, contenedor_lp, pallet_clave, pallet_lp)
    VALUES
      (:id, :art, :lote, :fecha, :ori, :dst, :cant, 0, NULL,
       :tipo, :usr, :alm1, :alm2, :alm3, 1,
       :ref, :cclave, :clp, :pclave, :plp)
  ");

  for ($i = 0; $i < count($det); $i++) {
    $r = $det[$i];
    $nextCardex++;
    $last_cardex_id = $nextCardex;

    $art = (string) $r['cve_articulo'];
    $lote = (string) $r['cve_lote'];
    $cant = (string) $r['cantidad'];

    // En tu modelo existen columnas para pallet y contenedor; llenamos según tipo de c_charolas
    $isCont = (stripos($tipoO, 'CONT') !== false) ? true : false;
    $cclave = $isCont ? (string) $claveDestinoEnCardex : null;
    $clp = $isCont ? (string) $lpDestinoEnCardex : null;
    $pclave = !$isCont ? (string) $claveDestinoEnCardex : null;
    $plp = !$isCont ? (string) $lpDestinoEnCardex : null;

    $ins->bindValue(':id', $nextCardex, PDO::PARAM_INT);
    $ins->bindValue(':art', $art);
    $ins->bindValue(':lote', $lote);
    $ins->bindValue(':fecha', $fecha);
    $ins->bindValue(':ori', $bl_origen);
    $ins->bindValue(':dst', $bl_destino);
    $ins->bindValue(':cant', $cant);
    $ins->bindValue(':tipo', $tipo_mov, PDO::PARAM_INT);
    $ins->bindValue(':usr', $usuario);
    $ins->bindValue(':alm1', $almO);
    $ins->bindValue(':alm2', $almO);
    $ins->bindValue(':alm3', $almO);
    $ins->bindValue(':ref', $folio);
    $ins->bindValue(':cclave', $cclave);
    $ins->bindValue(':clp', $clp);
    $ins->bindValue(':pclave', $pclave);
    $ins->bindValue(':plp', $plp);
    $ins->execute();
  }

  // Bitácora LP (t_movcharolas)
  $nextMov++;
  $st = $pdo->prepare("
    INSERT INTO t_movcharolas
      (id, id_kardex, Cve_Almac, ID_Contenedor, Fecha, Origen, Destino, Id_TipoMovimiento, Cve_Usuario, Status, EsCaja)
    VALUES
      (:id, :idk, :alm, :idcont, :fecha, :ori, :dst, :tipo, :usr, 'A', 'N')
  ");
  $st->execute([
    ':id' => $nextMov,
    ':idk' => $last_cardex_id,
    ':alm' => $almO,
    ':idcont' => $idContO,
    ':fecha' => $fecha,
    ':ori' => $bl_origen,
    ':dst' => $bl_destino,
    ':tipo' => $tipo_mov,
    ':usr' => $usuario,
  ]);

  // Bloqueo LP origen en MERGE (no reutilizable)
  // --- Actualizar Existencias (Inventory Snapshot) ---
  // Fix: Los usuarios reportan que no cambia el BL. Se debe actualizar ts_existenciatarima / ts_existenciacajas.

  // 1. Obtener datos clave del BL destino
  // Ya tenemos $ub validado arriba, pero necesitamos ids
  $stUB = $pdo->prepare("SELECT idy_ubica, cve_almac FROM c_ubicacion WHERE CodigoCSD = :bl LIMIT 1");
  $stUB->execute([':bl' => $bl_destino]);
  $rowUB = $stUB->fetch(PDO::FETCH_ASSOC);
  $id_ubica_dest = (int) $rowUB['idy_ubica'];
  $cve_almac_dest = (string) $rowUB['cve_almac']; // Cuidado: ts_existencia usa INT o VARCHAR? Schema dice int(11) para Idy y int(11) para Cve_Almac

  // 2. Ejecutar Update
  if ($modo === 'MOVE') {
    // MOVE: El LP completo (IDContenedor) se mueve de ubicación.
    // No cambiamos nTarima, solo su ubicación.

    // Tarimas: Actualizar ubicación y almacén en TODAS las filas (incluso existencia=0 para evitar "split locations")
    $updT = $pdo->prepare("UPDATE ts_existenciatarima SET Idy_Ubica = :u, cve_almac = :a WHERE nTarima = :id");
    $updT->execute([':u' => $id_ubica_dest, ':a' => $cve_almac_dest, ':id' => $idContO]);

    // Cajas: Actualizar ubicación y almacén
    $updC = $pdo->prepare("UPDATE ts_existenciacajas SET idy_ubica = :u, Cve_Almac = :a WHERE nTarima = :id");
    $updC->execute([':u' => $id_ubica_dest, ':a' => $cve_almac_dest, ':id' => $idContO]);

    // MOVE: El LP se mueve CON su inventario, por lo tanto debe permanecer ACTIVO
    // Solo se inactiva en MERGE cuando el LP queda vacío
    // NO inactivar aquí - el LP sigue teniendo stock

    // OJO: NO inactivamos c_charolas en MOVE, porque el LP sigue existiendo, solo se movió.
    // Solo actualizamos su ubicación en el maestro si c_charolas tuviera columna de ubicación (que no parece tener, lo saca de v_inv)

  } else { // MERGE
    // MERGE: El contenido del LP Origen pasa al LP Destino.
    // Cambiamos nTarima = IDContenedorDestino Y ubicación.

    // Tarimas: Transferir a nuevo LP y actualizar ubicación (BL se deriva de idy_ubica)
    // FIX: Mover TODO (incluyendo existencias 0) para no dejar huerfanos
    $updT = $pdo->prepare("UPDATE ts_existenciatarima SET nTarima = :newID, Idy_Ubica = :u, cve_almac = :a WHERE nTarima = :oldID");
    $updT->execute([
      ':newID' => $idContD,
      ':u' => $id_ubica_dest,
      ':a' => $cve_almac_dest,
      ':oldID' => $idContO
    ]);

    // Cajas: Transferir a nuevo LP y actualizar ubicación
    $updC = $pdo->prepare("UPDATE ts_existenciacajas SET nTarima = :newID, idy_ubica = :u, Cve_Almac = :a WHERE nTarima = :oldID");
    $updC->execute([
      ':newID' => $idContD,
      ':u' => $id_ubica_dest,
      ':a' => $cve_almac_dest,
      ':oldID' => $idContO
    ]);

    // En MERGE, el LP Origen queda vacío -> Inactivar
    $st = $pdo->prepare("UPDATE c_charolas SET Activo = 0 WHERE IDContenedor = :id");
    $st->execute([':id' => $idContO]);
  }

  $pdo->commit();

  respond([
    'ok' => 1,
    'service' => 'traslado_lp_commit',
    'modo' => $modo,
    'folio' => $folio,
    'bl_origen' => $bl_origen,
    'bl_destino' => $bl_destino,
    'cvelp_origen' => $cvelp_origen,
    'cvelp_destino' => $cvelp_destino,
    'id_kardex' => $last_cardex_id,
    'id_movcharolas' => $nextMov
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction())
    $pdo->rollBack();
  respond(['ok' => 0, 'error' => $e->getMessage() . ' [traslado_lp_commit]'], 500);
}
