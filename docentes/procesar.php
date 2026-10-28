<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';

    // ==========================================
    // CASO 1: RENOVACIÓN RÁPIDA DE CONTRATO
    // ==========================================
    if ($accion === 'renovar') {
        $id_persona = (int)($_POST['id_persona'] ?? 0);
        $fecha_contratacion = $_POST['fecha_contratacion'] ?? date('Y-m-d');
        $estado_laboral = $_POST['estado_laboral'] ?? 'ACTIVO';

        if (!$id_persona) {
            header("Location: index.php?msg=error");
            exit;
        }

        try {
            $stmt = $conexion->prepare(
                "UPDATE docentes 
                 SET fecha_contratacion = :fecha,
                     estado_laboral = :estado
                 WHERE id_persona = :id"
            );
            $stmt->execute([
                ':fecha' => $fecha_contratacion,
                ':estado' => $estado_laboral,
                ':id' => $id_persona
            ]);

            header("Location: index.php?msg=renovado");
            exit;
        } catch (Exception $e) {
            die("Error al renovar contrato: " . $e->getMessage() . " <br><a href='index.php'>Volver</a>");
        }
    }

    // ==========================================
    // CASO 2: CONTRATACIÓN NUEVA O EDICIÓN COMPLETA
    // ==========================================
    $id_persona         = !empty($_POST['id_persona']) ? (int)$_POST['id_persona'] : null;
    $nombres            = trim($_POST['nombres'] ?? '');
    $apellidos          = trim($_POST['apellidos'] ?? '');
    $documento_identidad= trim($_POST['documento_identidad'] ?? '');
    $telefono           = trim($_POST['telefono'] ?? '');
    $telefono2          = trim($_POST['telefono2'] ?? '');
    $correo             = trim($_POST['correo'] ?? '');
    $fecha_nacimiento   = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $sexo               = $_POST['sexo'] ?? 'NO_ESPECIFICA';
    $estado_civil       = !empty($_POST['estado_civil']) ? $_POST['estado_civil'] : null;
    $direccion          = trim($_POST['direccion'] ?? '');

    // Datos del contrato
    $codigo_docente     = trim($_POST['codigo_docente'] ?? '');
    $fecha_contratacion = $_POST['fecha_contratacion'] ?? date('Y-m-d');
    $estado_laboral     = strtoupper($_POST['estado_laboral'] ?? 'ACTIVO');
    $turno              = $_POST['turno'] ?? 'MANANA';
    $observaciones_doc  = trim($_POST['observaciones'] ?? '');

    // Datos académicos
    $id_especialidad    = !empty($_POST['id_especialidad']) ? (int)$_POST['id_especialidad'] : null;
    $id_tipo_credencial = !empty($_POST['id_tipo_credencial']) ? (int)$_POST['id_tipo_credencial'] : 1;
    $titulo             = trim($_POST['titulo'] ?? '');
    $institucion_emisora= trim($_POST['institucion_emisora'] ?? '');

    try {
        $conexion->beginTransaction();

        if ($id_persona) {
            // ------------------------------------------
            // ACTUALIZACIÓN DE DOCENTE EXISTENTE
            // ------------------------------------------
            // 1. Actualizar personas
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
                           direccion           = :dir
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
                ':id'        => $id_persona
            ]);

            // 2. Actualizar docentes
            $sqlDoc = "UPDATE docentes
                       SET codigo_docente    = :codigo,
                           fecha_contratacion= :fecha,
                           estado_laboral    = :estado,
                           turno             = :turno,
                           observaciones     = :obs
                       WHERE id_persona = :id";
            $stmtDoc = $conexion->prepare($sqlDoc);
            $stmtDoc->execute([
                ':codigo' => $codigo_docente,
                ':fecha'  => $fecha_contratacion,
                ':estado' => $estado_laboral,
                ':turno'  => $turno,
                ':obs'    => $observaciones_doc ?: null,
                ':id'     => $id_persona
            ]);

            // 3. Actualizar especialidad
            if ($id_especialidad) {
                $conexion->prepare("DELETE FROM docente_especialidad WHERE id_docente = :id")->execute([':id' => $id_persona]);
                $stmtEsp = $conexion->prepare("INSERT INTO docente_especialidad (id_docente, id_especialidad) VALUES (:id, :idesp)");
                $stmtEsp->execute([':id' => $id_persona, ':idesp' => $id_especialidad]);
            }

            // 4. Actualizar o insertar credencial académica
            if ($titulo !== '') {
                $checkCred = $conexion->prepare("SELECT id_credencial FROM credenciales_docentes WHERE id_docente = :id LIMIT 1");
                $checkCred->execute([':id' => $id_persona]);
                $idCred = $checkCred->fetchColumn();

                if ($idCred) {
                    $sqlCred = "UPDATE credenciales_docentes 
                                SET titulo = :titulo,
                                    institucion_emisora = :inst,
                                    id_tipo_credencial = :tipo
                                WHERE id_credencial = :idcred";
                    $stmtCred = $conexion->prepare($sqlCred);
                    $stmtCred->execute([
                        ':titulo' => $titulo,
                        ':inst'   => $institucion_emisora ?: null,
                        ':tipo'   => $id_tipo_credencial,
                        ':idcred' => $idCred
                    ]);
                } else {
                    $sqlCred = "INSERT INTO credenciales_docentes 
                                (id_docente, id_tipo_credencial, titulo, institucion_emisora, estado_verificacion)
                                VALUES 
                                (:id, :tipo, :titulo, :inst, 'VERIFICADA')";
                    $stmtCred = $conexion->prepare($sqlCred);
                    $stmtCred->execute([
                        ':id'     => $id_persona,
                        ':tipo'   => $id_tipo_credencial,
                        ':titulo' => $titulo,
                        ':inst'   => $institucion_emisora ?: null
                    ]);
                }
            }

            $conexion->commit();
            header("Location: index.php?msg=actualizado");
            exit;

        } else {
            // ------------------------------------------
            // REGISTRO DE NUEVA CONTRATACIÓN DOCENTE
            // ------------------------------------------
            if ($nombres === '' || $apellidos === '') {
                throw new Exception("Nombres y apellidos son obligatorios para la contratación.");
            }

            // 1. Insertar en tabla personas
            $sqlPer = "INSERT INTO personas 
                       (documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, estado_civil,
                        telefono, telefono2, correo, direccion, estado)
                       VALUES 
                       (:ci, :nombres, :apellidos, :fnac, :sexo, :ecivil, :tel, :tel2, :correo, :dir, 'ACTIVO')";
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
            ]);

            $id_persona = (int)$conexion->lastInsertId();

            // 2. Insertar en tabla docentes
            $sqlDoc = "INSERT INTO docentes 
                       (id_persona, codigo_docente, fecha_contratacion, estado_laboral, turno, observaciones)
                       VALUES 
                       (:id, :codigo, :fecha, :estado, :turno, :obs)";
            $stmtDoc = $conexion->prepare($sqlDoc);
            $stmtDoc->execute([
                ':id'     => $id_persona,
                ':codigo' => $codigo_docente,
                ':fecha'  => $fecha_contratacion,
                ':estado' => $estado_laboral,
                ':turno'  => $turno,
                ':obs'    => $observaciones_doc ?: null
            ]);

            // 3. Vincular especialidad
            if ($id_especialidad) {
                $stmtEsp = $conexion->prepare("INSERT INTO docente_especialidad (id_docente, id_especialidad) VALUES (:id, :idesp)");
                $stmtEsp->execute([':id' => $id_persona, ':idesp' => $id_especialidad]);
            }

            // 4. Registrar credencial y título profesional
            if ($titulo !== '') {
                $sqlCred = "INSERT INTO credenciales_docentes 
                            (id_docente, id_tipo_credencial, titulo, institucion_emisora, estado_verificacion)
                            VALUES 
                            (:id, :tipo, :titulo, :inst, 'VERIFICADA')";
                $stmtCred = $conexion->prepare($sqlCred);
                $stmtCred->execute([
                    ':id'     => $id_persona,
                    ':tipo'   => $id_tipo_credencial,
                    ':titulo' => $titulo,
                    ':inst'   => $institucion_emisora ?: null
                ]);
            }

            $conexion->commit();
            header("Location: index.php?msg=creado");
            exit;
        }

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        die("Error al procesar el docente: " . $e->getMessage() . " <br><br><a href='index.php'>Volver al listado</a>");
    }

} else {
    header("Location: index.php");
    exit;
}