<?php
class TaskHelper {

  public static function validarTask(PDO $pdo, ?int $task_id): void {
    if (!$task_id) return;

    $st = $pdo->prepare("SELECT id, estado FROM t_tareas WHERE id=?");
    $st->execute([$task_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      throw new Exception("task_id no existe");
    }
    if ($row['estado'] !== 'ABIERTA') {
      throw new Exception("task_id no está ABIERTA");
    }
  }

}
