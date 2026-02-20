<?php
require_once '../../bi/_menu_global.php';
require_once '../../../app/db.php';

$pdo = db_pdo();

// Usuario autenticado (ajusta si tu variable es distinta)
$usuario = $_SESSION['usuario'] ?? null;
$idUser  = $_SESSION['id_user'] ?? null;

echo "<h3>Diagnóstico Vendedor</h3>";
echo "<pre>";
echo "Usuario: $usuario\n";
echo "id_user: $idUser\n\n";

/* ===================== MÉTODO A ===================== */
echo "---- MÉTODO A (id_user → relvendrutas) ----\n";

$rowA = db_one("
    SELECT rv.IdVendedor, rv.IdRuta
    FROM relvendrutas rv
    WHERE rv.IdVendedor = ?
    LIMIT 1
", [$idUser]);

print_r($rowA);

/* ===================== MÉTODO B ===================== */
echo "\n---- MÉTODO B (cve_usuario → t_vendedores) ----\n";

$idVendedorB = db_val("
    SELECT id_vendedor
    FROM t_vendedores
    WHERE Cve_Vendedor = ?
    LIMIT 1
", [$usuario]);

echo "id_vendedor encontrado: $idVendedorB\n";

$rowB = db_one("
    SELECT rv.IdVendedor, rv.IdRuta
    FROM relvendrutas rv
    WHERE rv.IdVendedor = ?
    LIMIT 1
", [$idVendedorB]);

print_r($rowB);

echo "</pre>";
