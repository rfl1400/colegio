<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    $conexion->beginTransaction();

    // 1. Eliminar vínculos en tablas hijas
    $conexion->prepare("DELETE FROM docente_especialidad WHERE id_docente = :id")->execute([':id' => $id]);
    $conexion->prepare("DELETE FROM credenciales_docentes WHERE id_docente = :id")->execute([':id' => $id]);

    // 2. Eliminar de la tabla docentes
    $stmt = $conexion->prepare("DELETE FROM docentes WHERE id_persona = :id");
    $stmt->execute([':id' => $id]);

    $conexion->commit();

    header("Location: index.php?msg=eliminado");
    exit;

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    header("Location: index.php?msg=error");
    exit;
}