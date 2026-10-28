<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    $conexion->beginTransaction();

    // 1. Eliminar vínculos en tablas dependientes
    $conexion->prepare("DELETE FROM matriculas WHERE id_estudiante = :id")->execute([':id' => $id]);
    $conexion->prepare("DELETE FROM contactos_estudiantes WHERE id_estudiante = :id")->execute([':id' => $id]);

    // 2. Eliminar de estudiantes
    $stmt = $conexion->prepare("DELETE FROM estudiantes WHERE id_persona = :id");
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