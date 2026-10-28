<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_persona          = !empty($_POST['id_persona']) ? (int)$_POST['id_persona'] : null;
    $nombres             = trim($_POST['nombres'] ?? '');
    $apellidos           = trim($_POST['apellidos'] ?? '');
    $documento_identidad = trim($_POST['documento_identidad'] ?? '');
    $fecha_nacimiento    = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $sexo                = $_POST['sexo'] ?? 'NO_ESPECIFICA';
    $direccion           = trim($_POST['direccion'] ?? '');
    $telefono_alumno     = trim($_POST['telefono'] ?? '');

    // Datos escolares
    $codigo_estudiante   = trim($_POST['codigo_estudiante'] ?? '');
    $fecha_ingreso       = $_POST['fecha_ingreso'] ?? date('Y-m-d');
    $observaciones       = trim($_POST['observaciones'] ?? '');
    $colegio_origen      = trim($_POST['colegio_origen'] ?? '');
    $id_oferta           = !empty($_POST['id_oferta']) ? (int)$_POST['id_oferta'] : null;
    $tipo_ingreso        = $_POST['tipo_ingreso'] ?? 'NUEVO';
    $estado_matricula    = $_POST['estado_matricula'] ?? 'ACTIVA';

    if ($colegio_origen !== '') {
        $observaciones = "[Procedencia: $colegio_origen] " . $observaciones;
    }

    // Datos del apoderado titular
    $tutor_nombre        = trim($_POST['tutor_nombre'] ?? '');
    $tutor_parentesco    = trim($_POST['tutor_parentesco'] ?? 'Padre');
    $tutor_telefono      = trim($_POST['tutor_telefono'] ?? '');
    $tutor_telefono2     = trim($_POST['tutor_telefono2'] ?? '');
    $tutor_correo        = trim($_POST['tutor_correo'] ?? '');
    $tutor_ocupacion     = trim($_POST['tutor_ocupacion'] ?? '');
    $tutor_direccion     = trim($_POST['tutor_direccion'] ?? '');
    $tutor_obs           = trim($_POST['tutor_obs'] ?? '');

    // Tutores / Apoderados adicionales (dinámicos)
    $extra_nombres       = $_POST['extra_tutor_nombre'] ?? [];
    $extra_parentescos   = $_POST['extra_tutor_parentesco'] ?? [];
    $extra_telefonos     = $_POST['extra_tutor_telefono'] ?? [];
    $extra_telefonos2    = $_POST['extra_tutor_telefono2'] ?? [];
    $extra_correos       = $_POST['extra_tutor_correo'] ?? [];
    $extra_ocupaciones   = $_POST['extra_tutor_ocupacion'] ?? [];
    $extra_direcciones   = $_POST['extra_tutor_direccion'] ?? [];
    $extra_emergencias   = $_POST['extra_tutor_emergencia'] ?? [];
    $extra_obs           = $_POST['extra_tutor_obs'] ?? [];

    try {
        $conexion->beginTransaction();

        if ($id_persona) {
            // ==========================================
            // MODO ACTUALIZACIÓN
            // ==========================================
            // 1. Actualizar tabla personas
            $sqlPer = "UPDATE personas 
                       SET nombres = :nombres,
                           apellidos = :apellidos,
                           documento_identidad = :ci,
                           fecha_nacimiento = :fnac,
                           sexo = :sexo,
                           direccion = :dir,
                           telefono = :tel
                       WHERE id_persona = :id";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':ci'      => $documento_identidad ?: null,
                ':fnac'    => $fecha_nacimiento,
                ':sexo'    => $sexo,
                ':dir'     => $direccion ?: null,
                ':tel'     => $telefono_alumno ?: null,
                ':id'      => $id_persona
            ]);

            // 2. Actualizar tabla estudiantes
            $sqlEst = "UPDATE estudiantes
                       SET codigo_estudiante = :codigo,
                           fecha_ingreso     = :fecha,
                           observaciones     = :obs
                       WHERE id_persona = :id";
            $stmtEst = $conexion->prepare($sqlEst);
            $stmtEst->execute([
                ':codigo' => $codigo_estudiante,
                ':fecha'  => $fecha_ingreso,
                ':obs'    => $observaciones ?: null,
                ':id'     => $id_persona
            ]);

            // 3. Actualizar matrícula en curso
            if ($id_oferta) {
                $checkMat = $conexion->prepare("SELECT id_matricula FROM matriculas WHERE id_estudiante = :id LIMIT 1");
                $checkMat->execute([':id' => $id_persona]);
                $idMat = $checkMat->fetchColumn();

                if ($idMat) {
                    $sqlMat = "UPDATE matriculas 
                               SET id_oferta = :oferta,
                                   tipo_ingreso = :tipo,
                                   estado = :estado
                               WHERE id_matricula = :idmat";
                    $stmtMat = $conexion->prepare($sqlMat);
                    $stmtMat->execute([
                        ':oferta' => $id_oferta,
                        ':tipo'   => $tipo_ingreso,
                        ':estado' => $estado_matricula,
                        ':idmat'  => $idMat
                    ]);
                } else {
                    $sqlMat = "INSERT INTO matriculas 
                               (id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado)
                               VALUES 
                               (:id, :oferta, :fecha, :tipo, :estado)";
                    $stmtMat = $conexion->prepare($sqlMat);
                    $stmtMat->execute([
                        ':id'     => $id_persona,
                        ':oferta' => $id_oferta,
                        ':fecha'  => $fecha_ingreso,
                        ':tipo'   => $tipo_ingreso,
                        ':estado' => $estado_matricula
                    ]);
                }
            }

            // 4. Actualizar tutor titular
            if ($tutor_nombre !== '') {
                $checkTut = $conexion->prepare("SELECT id_contacto FROM contactos_estudiantes WHERE id_estudiante = :id AND es_representante = 1 LIMIT 1");
                $checkTut->execute([':id' => $id_persona]);
                $idTut = $checkTut->fetchColumn();

                if ($idTut) {
                    $sqlTut = "UPDATE contactos_estudiantes 
                               SET nombre_completo = :nom,
                                   parentesco = :par,
                                   telefono = :tel,
                                   telefono2 = :tel2,
                                   correo = :cor,
                                   ocupacion = :ocup,
                                   direccion = :dir,
                                   observaciones = :obs
                               WHERE id_contacto = :idtut";
                    $stmtTut = $conexion->prepare($sqlTut);
                    $stmtTut->execute([
                        ':nom'   => $tutor_nombre,
                        ':par'   => $tutor_parentesco,
                        ':tel'   => $tutor_telefono,
                        ':tel2'  => $tutor_telefono2 ?: null,
                        ':cor'   => $tutor_correo ?: null,
                        ':ocup'  => $tutor_ocupacion ?: null,
                        ':dir'   => $tutor_direccion ?: null,
                        ':obs'   => $tutor_obs ?: null,
                        ':idtut' => $idTut
                    ]);
                } else {
                    $sqlTut = "INSERT INTO contactos_estudiantes 
                               (id_estudiante, nombre_completo, parentesco, telefono, telefono2, correo, ocupacion, direccion, es_contacto_emergencia, es_representante, observaciones)
                               VALUES 
                               (:id, :nom, :par, :tel, :tel2, :cor, :ocup, :dir, 1, 1, :obs)";
                    $stmtTut = $conexion->prepare($sqlTut);
                    $stmtTut->execute([
                        ':id'   => $id_persona,
                        ':nom'  => $tutor_nombre,
                        ':par'  => $tutor_parentesco,
                        ':tel'  => $tutor_telefono,
                        ':tel2' => $tutor_telefono2 ?: null,
                        ':cor'  => $tutor_correo ?: null,
                        ':ocup' => $tutor_ocupacion ?: null,
                        ':dir'  => $tutor_direccion ?: null,
                        ':obs'  => $tutor_obs ?: null
                    ]);
                }
            }

            // 5. Eliminar contactos no representantes antiguos y reinsertar los nuevos
            $delContacts = $conexion->prepare("DELETE FROM contactos_estudiantes WHERE id_estudiante = :id AND es_representante = 0");
            $delContacts->execute([':id' => $id_persona]);

            $sqlInsExtra = "INSERT INTO contactos_estudiantes
                            (id_estudiante, nombre_completo, parentesco, telefono, telefono2, correo, ocupacion, direccion, es_contacto_emergencia, es_representante, observaciones)
                            VALUES
                            (:id, :nom, :par, :tel, :tel2, :cor, :ocup, :dir, :emg, 0, :obs)";
            $stmtExtra = $conexion->prepare($sqlInsExtra);

            if (is_array($extra_nombres)) {
                for ($i = 0; $i < count($extra_nombres); $i++) {
                    $nom = trim($extra_nombres[$i] ?? '');
                    if ($nom === '') continue;
                    $par = trim($extra_parentescos[$i] ?? 'Familiar');
                    $tel = trim($extra_telefonos[$i] ?? '');
                    $tel2 = trim($extra_telefonos2[$i] ?? '');
                    $cor = trim($extra_correos[$i] ?? '');
                    $ocup = trim($extra_ocupaciones[$i] ?? '');
                    $dir = trim($extra_direcciones[$i] ?? '');
                    $emg = isset($extra_emergencias[$i]) ? (int)$extra_emergencias[$i] : 1;
                    $obs = trim($extra_obs[$i] ?? '');

                    $stmtExtra->execute([
                        ':id'   => $id_persona,
                        ':nom'  => $nom,
                        ':par'  => $par,
                        ':tel'  => $tel,
                        ':tel2' => $tel2 ?: null,
                        ':cor'  => $cor ?: null,
                        ':ocup' => $ocup ?: null,
                        ':dir'  => $dir ?: null,
                        ':emg'  => $emg,
                        ':obs'  => $obs ?: null
                    ]);
                }
            }

            $conexion->commit();
            header("Location: index.php?msg=actualizado");
            exit;

        } else {
            // ==========================================
            // MODO NUEVO REGISTRO / MATRÍCULA
            // ==========================================
            if ($nombres === '' || $apellidos === '') {
                throw new Exception("Nombres y apellidos son obligatorios.");
            }

            // 1. Insertar en tabla personas
            $sqlPer = "INSERT INTO personas 
                       (documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, direccion, telefono, estado)
                       VALUES 
                       (:ci, :nombres, :apellidos, :fnac, :sexo, :dir, :tel, 'ACTIVO')";
            $stmtPer = $conexion->prepare($sqlPer);
            $stmtPer->execute([
                ':ci'        => $documento_identidad ?: null,
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':fnac'      => $fecha_nacimiento,
                ':sexo'      => $sexo,
                ':dir'       => $direccion ?: null,
                ':tel'       => $telefono_alumno ?: null
            ]);

            $id_persona = (int)$conexion->lastInsertId();

            // 2. Insertar en tabla estudiantes
            $sqlEst = "INSERT INTO estudiantes 
                       (id_persona, codigo_estudiante, fecha_ingreso, observaciones)
                       VALUES 
                       (:id, :codigo, :fecha, :obs)";
            $stmtEst = $conexion->prepare($sqlEst);
            $stmtEst->execute([
                ':id'     => $id_persona,
                ':codigo' => $codigo_estudiante,
                ':fecha'  => $fecha_ingreso,
                ':obs'    => $observaciones ?: null
            ]);

            // 3. Insertar matrícula en curso asignado
            if ($id_oferta) {
                $sqlMat = "INSERT INTO matriculas 
                           (id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado)
                           VALUES 
                           (:id, :oferta, :fecha, :tipo, :estado)";
                $stmtMat = $conexion->prepare($sqlMat);
                $stmtMat->execute([
                    ':id'     => $id_persona,
                    ':oferta' => $id_oferta,
                    ':fecha'  => $fecha_ingreso,
                    ':tipo'   => $tipo_ingreso,
                    ':estado' => $estado_matricula
                ]);
            }

            // 4. Insertar apoderado / tutor titular
            if ($tutor_nombre !== '') {
                $sqlTut = "INSERT INTO contactos_estudiantes 
                           (id_estudiante, nombre_completo, parentesco, telefono, telefono2, correo, ocupacion, direccion, es_contacto_emergencia, es_representante, observaciones)
                           VALUES 
                           (:id, :nom, :par, :tel, :tel2, :cor, :ocup, :dir, 1, 1, :obs)";
                $stmtTut = $conexion->prepare($sqlTut);
                $stmtTut->execute([
                    ':id'   => $id_persona,
                    ':nom'  => $tutor_nombre,
                    ':par'  => $tutor_parentesco,
                    ':tel'  => $tutor_telefono,
                    ':tel2' => $tutor_telefono2 ?: null,
                    ':cor'  => $tutor_correo ?: null,
                    ':ocup' => $tutor_ocupacion ?: null,
                    ':dir'  => $tutor_direccion ?: null,
                    ':obs'  => $tutor_obs ?: null
                ]);
            }

            // 5. Insertar apoderados/contactos adicionales
            if (is_array($extra_nombres)) {
                $sqlInsExtra = "INSERT INTO contactos_estudiantes
                                (id_estudiante, nombre_completo, parentesco, telefono, telefono2, correo, ocupacion, direccion, es_contacto_emergencia, es_representante, observaciones)
                                VALUES
                                (:id, :nom, :par, :tel, :tel2, :cor, :ocup, :dir, :emg, 0, :obs)";
                $stmtExtra = $conexion->prepare($sqlInsExtra);

                for ($i = 0; $i < count($extra_nombres); $i++) {
                    $nom = trim($extra_nombres[$i] ?? '');
                    if ($nom === '') continue;
                    $par = trim($extra_parentescos[$i] ?? 'Familiar');
                    $tel = trim($extra_telefonos[$i] ?? '');
                    $tel2 = trim($extra_telefonos2[$i] ?? '');
                    $cor = trim($extra_correos[$i] ?? '');
                    $ocup = trim($extra_ocupaciones[$i] ?? '');
                    $dir = trim($extra_direcciones[$i] ?? '');
                    $emg = isset($extra_emergencias[$i]) ? (int)$extra_emergencias[$i] : 1;
                    $obs = trim($extra_obs[$i] ?? '');

                    $stmtExtra->execute([
                        ':id'   => $id_persona,
                        ':nom'  => $nom,
                        ':par'  => $par,
                        ':tel'  => $tel,
                        ':tel2' => $tel2 ?: null,
                        ':cor'  => $cor ?: null,
                        ':ocup' => $ocup ?: null,
                        ':dir'  => $dir ?: null,
                        ':emg'  => $emg,
                        ':obs'  => $obs ?: null
                    ]);
                }
            }

            $conexion->commit();
            header("Location: index.php?msg=creado");
            exit;
        }

    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        die("Error al procesar la matrícula del estudiante: " . $e->getMessage() . " <br><br><a href='index.php'>Volver al listado</a>");
    }

} else {
    header("Location: index.php");
    exit;
}