<?php
if (session_status() === PHP_SESSION_NONE) //@session_start();

  if (empty($_SESSION['username']) || empty($_SESSION['cve_almac'])) {
    header('Location: /assistpro_kardex_fc/public/login.php?err=Inicie sesión');
    exit;
  }

function acl_where_emp_sql($alias = 'v')
{
  $all = $_SESSION['empresas_all'] ?? false;
  $ids = $_SESSION['empresas'] ?? [];
  if ($all || !is_array($ids) || count($ids) === 0)
    return '1=1';
  $place = implode(',', array_map('intval', $ids));
  return "$alias.empresa_id IN ($place)";
}
function acl_where_alm_sql($alias = 'v')
{
  $alm = $_SESSION['cve_almac'] ?? '';
  return $alm ? "TRIM($alias.cve_almac)=TRIM('" . str_replace("'", "''", $alm) . "')" : '1=1';
}
