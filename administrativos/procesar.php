<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_persona            = !empty($_POST['id_persona']) ? (int)$_POST['id_persona'] : null;
    $codigo_administrativo = trim($_POST['codigo_administrativo'] ?? '');
    $cargo                 = trim($_POST['cargo'] ?? '');
    $departamento          = trim($_POST['departamento'] ?? '');
    $turno                 = $_POST['turno'] ?? 'MANANA';
    $salario               = !empty($_POST['salario']) ? (float)$_POST['salario'] : null;
    $fecha_contratacion    = $_POST['fecha_contratacion'] ?? date('Y-m-d');
    $estado_laboral        = strtolower($_POST['estado_laboral'] ?? 'activo');
    $observaciones_adm     = trim($_POST['observaciones'] ?? '');

    // Datos personales
    $nombres             = trim($_POST['nombres'] ?? '');
    $apellidos           = trim($_POST['apellidos'] ?? '');
    $documento_identidad = trim($_POST['documento_identidad'] ?? '');
    $fecha_nacimiento    = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $sexo                = $_POST['sexo'] ?? 'NO_ESPECIFICA';
    $estado_civil        = !empty($_POST['estado_civil']) ? $_POST['estado_civil'] : null;
    $ocupacion           = trim($_POST['ocupacion'] ?? '');
    $telefono            = trim($_POST['telefono'] ?? '');
    $telefono2           = trim($_POST['telefono2'] ?? '');
    $correo              = trim($_POST['correo'] ?? '');
    $direccion           = trim($_POST['direccion'] ?? '');

    // Contactos de emergencia (arrays)
    $c_nombres    = $_POST['contacto_nombre']    ?? [];
    $c_relaciones = $_POST['contacto_relacion']  ?? [];
    $c_telefonos  = $_POST['contacto_telefono']  ?? [];
    $c_correos    = $_POST['contacto_correo']    ?? [];
    $c_direcciones= $_POST['contacto_direccion'] ?? [];

    // Formación académica
    $nivel_formacion     = trim($_POST['nivel_formacion'] ?? '');
    $titulo_formacion    = trim($_POST['titulo_formacion'] ?? '');
    $inst_formacion      = trim($_POST['institucion_formacion'] ?? '');
    $inst_anterior       = trim($_POST['institucion_anterior'] ?? '');
    // Notas para incluir en observaciones administrativas
    $notasAcademicas = '';
    if ($nivel_formacion || $titulo_formacion || $inst_formacion || $inst_anterior) {
        $notasAcademicas = "\n[Formación: $nivel_formacion - $titulo_formacion | Institución: $inst_formacion | Anterior: $inst_anterior]";
    }
    $obsCompleto = $observaciones_adm . $notasAcademicas;

    try {
        $conexion->beginTransaction();

        if ($id_persona) {
            // ==========================================
            // MODO ACTUALIZACIÓN
            // ==========================================
            $sqlPer = "UPDATE personas
                       SET nombres             = :nombres,
                           apellidos           = :apellidos,
                           documento_identidad = :ci,
                           fecha_nacimiento    = :fnac,
                           sexo                = :sexo,
                           estado_civil        = :ecivil,
                           telefono            = :tel,
                           telefono2           = :tel2,
                           correo              = :correo,
                           direccion           = :dir,
                           ocupacion           = :ocup
                       WHERE id_persona = :id";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->execute([
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':ci'        => $documento_identidad ?: null,
                ':fnac'      => $fecha_nacimiento,
                ':sexo'      => $sexo,
                ':ecivil'    => $estado_civil,
                ':tel'       => $telefono ?: null,
                ':tel2'      => $telefono2 ?: null,
                ':correo'    => $correo ?: null,
                ':dir'       => $direccion ?: null,
                ':ocup'      => $ocupacion ?: null,
                ':id'        => $id_persona
            ]);

            $sqlAdm = "UPDATE administrativos
                       SET codigo_administrativo = :codigo,
                           cargo                 = :cargo,
                           departamento          = :dpto,
                           turno                 = :turno,
                           salario               = :salario,
                           fecha_contratacion    = :fecha,
                           estado_laboral        = :estado,
                           observaciones         = :obs
                       WHERE id_persona = :id";
            $stmtAdm = $conexion->prepare($sqlAdm);
            $stmtAdm->execute([
                ':codigo'  => $codigo_administrativo,
                ':cargo'   => $cargo,
                ':dpto'    => $departamento ?: null,
                ':turno'   => $turno,
                ':salario' => $salario,
                ':fecha'   => $fecha_contratacion,
                ':estado'  => $estado_laboral,
                ':obs'     => $obsCompleto ?: null,
                ':id'      => $id_persona
            ]);

            $conexion->commit();
            header("Location: index.php?msg=actualizado");
            exit;

        } else {
            // ==========================================
            // MODO INSERCIÓN
            // ==========================================
            if ($nombres === '' || $apellidos === '') {
                throw new Exception("Nombres y apellidos son obligatorios.");
            }

            $sqlPer = "INSERT INTO personas
                       (documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, estado_civil,
                        telefono, telefono2, correo, direccion, ocupacion, estado)
                       VALUES
                       (:ci, :nombres, :apellidos, :fnac, :sexo, :ecivil, :tel, :tel2, :correo, :dir, :ocup, 'ACTIVO')";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->execute([
                ':ci'        => $documento_identidad ?: null,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':fnac'      => $fecha_nacimiento,
                ':sexo'      => $sexo,
                ':ecivil'    => $estado_civil,
                ':tel'       => $telefono ?: null,
                ':tel2'      => $telefono2 ?: null,
                ':correo'    => $correo ?: null,
                ':dir'       => $direccion ?: null,
                ':ocup'      => $ocupacion ?: null,
            ]);

            $id_persona = (int)$conexion->lastInsertId();

            $sqlAdm = "INSERT INTO administrativos
                       (id_persona, codigo_administrativo, cargo, departamento, turno, salario,
                        fecha_contratacion, estado_laboral, observaciones)
                       VALUES
                       (:id_persona, :codigo, :cargo, :dpto, :turno, :salario, :fecha, :estado, :obs)";
            $stmtAdm = $conexion->prepare($sqlAdm);
            $stmtAdm->execute([
                ':id_persona' => $id_persona,
                ':codigo'     => $codigo_administrativo,
                ':cargo'      => $cargo,
                ':dpto'       => $departamento ?: null,
                ':turno'      => $turno,
                ':salario'    => $salario,
                ':fecha'      => $fecha_contratacion,
                ':estado'     => $estado_laboral,
                ':obs'        => $obsCompleto ?: null
            ]);

            $conexion->commit();
            header("Location: index.php?msg=creado");
            exit;
        }

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        die("Error al procesar el administrativo: " . $e->getMessage() . " <br><br><a href='index.php'>Volver al listado</a>");
    }
} else {
    header("Location: index.php");
    exit;
}