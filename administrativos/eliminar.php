<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

try {
    $conexion->beginTransaction();

    // 1. Eliminar registro de la tabla administrativos
    $sql = "DELETE FROM administrativos WHERE id_persona = :id";
    $stmt = $conexion->prepare($sql);
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