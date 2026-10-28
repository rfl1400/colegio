<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos del estudiante, persona y matrícula
$sql = "SELECT e.*,
               p.documento_identidad,
               p.nombres,
               p.apellidos,
               p.fecha_nacimiento,
               p.sexo,
               p.direccion,
               p.telefono,
               m.id_oferta,
               m.tipo_ingreso,
               m.estado AS estado_matricula
        FROM estudiantes e
        INNER JOIN personas p ON e.id_persona = p.id_persona
        LEFT JOIN matriculas m ON e.id_persona = m.id_estudiante
        WHERE e.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$est = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$est) {
    die("Estudiante no encontrado. <a href='index.php'>Volver al listado</a>");
}

// Cargar tutor titular
$stmtTut1 = $conexion->prepare("SELECT * FROM contactos_estudiantes WHERE id_estudiante = :id AND es_representante = 1 LIMIT 1");
$stmtTut1->execute([':id' => $id]);
$tutorTitular = $stmtTut1->fetch(PDO::FETCH_ASSOC) ?: [];

// Cargar tutores adicionales
$stmtTutExtras = $conexion->prepare("SELECT * FROM contactos_estudiantes WHERE id_estudiante = :id AND es_representante = 0 ORDER BY id_contacto ASC");
$stmtTutExtras->execute([':id' => $id]);
$tutoresExtras = $stmtTutExtras->fetchAll(PDO::FETCH_ASSOC);

// Cargar ofertas académicas activas agrupadas por nivel
$sqlOfertas = "SELECT o.id_oferta, g.anio AS gestion, gr.nombre AS grado, pa.nombre AS paralelo, n.nombre AS nivel, o.aula
               FROM ofertas_academicas o
               JOIN gestiones g ON o.id_gestion = g.id_gestion
               JOIN grados gr ON o.id_grado = gr.id_grado
               JOIN niveles_academicos n ON gr.id_nivel = n.id_nivel
               JOIN paralelos pa ON o.id_paralelo = pa.id_paralelo
               WHERE g.estado = 'ACTIVA'
               ORDER BY n.orden ASC, gr.id_grado ASC, pa.nombre ASC";
$ofertasRaw = $conexion->query($sqlOfertas)->fetchAll(PDO::FETCH_ASSOC);

$ofertasPorNivel = [];
foreach ($ofertasRaw as $of) {
    $nivel = $of['nivel'];
    if (!isset($ofertasPorNivel[$nivel])) {
        $ofertasPorNivel[$nivel] = [];
    }
    $ofertasPorNivel[$nivel][] = $of;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante | <?= htmlspecialchars($est['codigo_estudiante']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --card-bg: rgba(22, 29, 47, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --field-bg: rgba(15, 23, 42, 0.65);
            --field-border: rgba(255, 255, 255, 0.12);
            --field-focus: #60a5fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            background-image: 
                radial-gradient(at 15% 15%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(139, 92, 246, 0.08) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            padding: 2.5rem;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
            flex-wrap: wrap;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-badge {
            width: 52px;
            height: 52px;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .header h1 {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .header p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }

        .btn-back-header {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-back-header:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .section-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.75rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #93c5fd;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .section-badge {
            background: rgba(59, 130, 246, 0.18);
            color: #60a5fa;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.15rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.15rem;
        }

        @media (max-width: 820px) {
            .grid-3 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            .card {
                padding: 1.5rem;
            }
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 0.4rem;
        }

        label span.req {
            color: #ef4444;
            margin-left: 2px;
        }

        input[type="text"],
        input[type="date"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--field-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.85);
        }

        select option {
            background: #111827;
            color: #ffffff;
        }

        optgroup {
            color: #60a5fa;
            font-weight: 700;
            background: #1e293b;
        }

        .tutor-block {
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .tutor-block-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #93c5fd;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .btn-add-tutor {
            background: rgba(59, 130, 246, 0.1);
            border: 1px dashed rgba(59, 130, 246, 0.45);
            color: #93c5fd;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
            width: 100%;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .btn-add-tutor:hover {
            background: rgba(59, 130, 246, 0.18);
            border-color: #60a5fa;
            color: #ffffff;
        }

        .btn-remove-tutor {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            border-radius: 8px;
            padding: 0.3rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-remove-tutor:hover {
            background: rgba(239, 68, 68, 0.25);
            color: #ffffff;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.6rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="card">
        <header class="header">
            <div class="header-left">
                <div class="icon-badge">✏️</div>
                <div>
                    <h1>Editar Ficha Estudiantil</h1>
                    <p>Código: <strong style="color: #93c5fd;"><?= htmlspecialchars($est['codigo_estudiante']) ?></strong> &bull; <?= htmlspecialchars($est['nombres'] . ' ' . $est['apellidos']) ?></p>
                </div>
            </div>
            <a href="index.php" class="btn-back-header">
                ← Volver al Listado
            </a>
        </header>

        <form action="procesar.php" method="POST">
            <input type="hidden" name="id_persona" value="<?= $est['id_persona'] ?>">

            <!-- SECCIÓN 1: DATOS PERSONALES -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">1</span>
                    <span>👤 Información Personal del Estudiante</span>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombres del Estudiante <span class="req">*</span></label>
                        <input type="text" name="nombres" value="<?= htmlspecialchars($est['nombres']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Apellidos del Estudiante <span class="req">*</span></label>
                        <input type="text" name="apellidos" value="<?= htmlspecialchars($est['apellidos']) ?>" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>C.I. / RUDE Escolar <span class="req">*</span></label>
                        <input type="text" name="documento_identidad" value="<?= htmlspecialchars($est['documento_identidad'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Nacimiento <span class="req">*</span></label>
                        <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($est['fecha_nacimiento'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Sexo <span class="req">*</span></label>
                        <select name="sexo" required>
                            <option value="M" <?= $est['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $est['sexo'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                            <option value="OTRO" <?= $est['sexo'] === 'OTRO' ? 'selected' : '' ?>>Otro</option>
                            <option value="NO_ESPECIFICA" <?= $est['sexo'] === 'NO_ESPECIFICA' ? 'selected' : '' ?>>No Especifica</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Dirección Domiciliaria</label>
                        <input type="text" name="direccion" value="<?= htmlspecialchars($est['direccion'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Teléfono Propio del Estudiante</label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($est['telefono'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: CURSO Y MATRÍCULA -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">2</span>
                    <span>🏫 Asignación Escolar, Grado y Paralelo</span>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Código Estudiantil <span class="req">*</span></label>
                        <input type="text" name="codigo_estudiante" value="<?= htmlspecialchars($est['codigo_estudiante']) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #93c5fd;">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Curso, Nivel y Paralelo Asignado <span class="req">*</span></label>
                        <select name="id_oferta" required>
                            <option value="">-- Seleccionar Grado y Paralelo --</option>
                            <?php foreach ($ofertasPorNivel as $nivelNombre => $lista): ?>
                                <optgroup label="📚 NIVEL <?= strtoupper(htmlspecialchars($nivelNombre)) ?>">
                                    <?php foreach ($lista as $of): 
                                        $colorIcon = ($of['paralelo'] === 'Rojo') ? '🔴' : (($of['paralelo'] === 'Azul') ? '🔵' : '⚪');
                                    ?>
                                        <option value="<?= $of['id_oferta'] ?>" <?= $est['id_oferta'] == $of['id_oferta'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($of['grado']) ?> &bull; Paralelo <?= htmlspecialchars($of['paralelo']) ?> <?= $colorIcon ?> (<?= htmlspecialchars($of['aula']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Tipo de Ingreso <span class="req">*</span></label>
                        <select name="tipo_ingreso" required>
                            <option value="NUEVO" <?= ($est['tipo_ingreso'] ?? '') === 'NUEVO' ? 'selected' : '' ?>>NUEVO (Primer Ingreso)</option>
                            <option value="REGULAR" <?= ($est['tipo_ingreso'] ?? '') === 'REGULAR' ? 'selected' : '' ?>>REGULAR (Continuidad)</option>
                            <option value="TRASLADO" <?= ($est['tipo_ingreso'] ?? '') === 'TRASLADO' ? 'selected' : '' ?>>TRASLADO (De otra institución)</option>
                            <option value="REINGRESO" <?= ($est['tipo_ingreso'] ?? '') === 'REINGRESO' ? 'selected' : '' ?>>REINGRESO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Matrícula <span class="req">*</span></label>
                        <input type="date" name="fecha_ingreso" value="<?= htmlspecialchars($est['fecha_ingreso'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Estado de Matrícula <span class="req">*</span></label>
                        <select name="estado_matricula" required>
                            <option value="ACTIVA" <?= ($est['estado_matricula'] ?? '') === 'ACTIVA' ? 'selected' : '' ?>>ACTIVA</option>
                            <option value="TRASLADADA" <?= ($est['estado_matricula'] ?? '') === 'TRASLADADA' ? 'selected' : '' ?>>TRASLADADA</option>
                            <option value="RETIRADA" <?= ($est['estado_matricula'] ?? '') === 'RETIRADA' ? 'selected' : '' ?>>RETIRADA</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Observaciones Médicas, Pedagógicas o de Matrícula</label>
                    <textarea name="observaciones" rows="2"><?= htmlspecialchars($est['observaciones'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: FAMILIARES Y APODERADOS -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">3</span>
                    <span>👨‍👩‍👧‍👦 Familiares y Apoderados</span>
                </div>

                <!-- APODERADO TITULAR -->
                <div class="tutor-block">
                    <div class="tutor-block-title">
                        <span>👤 Apoderado Titular (Representante Legal Principal)</span>
                        <span style="color: #ef4444; font-size: 0.8rem;">* Requerido</span>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Nombre Completo del Apoderado <span class="req">*</span></label>
                            <input type="text" name="tutor_nombre" value="<?= htmlspecialchars($tutorTitular['nombre_completo'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Parentesco <span class="req">*</span></label>
                            <select name="tutor_parentesco" required>
                                <?php $parTit = $tutorTitular['parentesco'] ?? 'Padre'; ?>
                                <option value="Padre" <?= $parTit === 'Padre' ? 'selected' : '' ?>>Padre</option>
                                <option value="Madre" <?= $parTit === 'Madre' ? 'selected' : '' ?>>Madre</option>
                                <option value="Tutor Legal" <?= $parTit === 'Tutor Legal' ? 'selected' : '' ?>>Tutor Legal</option>
                                <option value="Abuelo/a" <?= $parTit === 'Abuelo/a' ? 'selected' : '' ?>>Abuelo/a</option>
                                <option value="Tío/a" <?= $parTit === 'Tío/a' ? 'selected' : '' ?>>Tío/a</option>
                                <option value="Hermano/a Mayor" <?= $parTit === 'Hermano/a Mayor' ? 'selected' : '' ?>>Hermano/a Mayor</option>
                                <option value="Otro" <?= $parTit === 'Otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teléfono Principal / WhatsApp <span class="req">*</span></label>
                            <input type="tel" name="tutor_telefono" value="<?= htmlspecialchars($tutorTitular['telefono'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Teléfono Secundario / Fijo</label>
                            <input type="tel" name="tutor_telefono2" value="<?= htmlspecialchars($tutorTitular['telefono2'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="tutor_correo" value="<?= htmlspecialchars($tutorTitular['correo'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Ocupación / Profesión</label>
                            <input type="text" name="tutor_ocupacion" value="<?= htmlspecialchars($tutorTitular['ocupacion'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Dirección del Apoderado</label>
                            <input type="text" name="tutor_direccion" value="<?= htmlspecialchars($tutorTitular['direccion'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Observaciones / Disponibilidad</label>
                            <input type="text" name="tutor_obs" value="<?= htmlspecialchars($tutorTitular['observaciones'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- APODERADOS ADICIONALES -->
                <div id="tutores-adicionales-container">
                    <?php 
                    $idx = 0;
                    foreach ($tutoresExtras as $tutEx): 
                        $idx++;
                    ?>
                        <div class="tutor-block" id="extra-tutor-<?= $idx ?>">
                            <div class="tutor-block-title">
                                <span>👥 Apoderado / Familiar Adicional #<?= $idx ?></span>
                            </div>
                            <button type="button" class="btn-remove-tutor" onclick="document.getElementById('extra-tutor-<?= $idx ?>').remove()">✕ Quitar</button>
                            
                            <div class="grid-3">
                                <div class="form-group">
                                    <label>Nombre Completo <span class="req">*</span></label>
                                    <input type="text" name="extra_tutor_nombre[]" value="<?= htmlspecialchars($tutEx['nombre_completo']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Parentesco <span class="req">*</span></label>
                                    <select name="extra_tutor_parentesco[]" required>
                                        <?php $pEx = $tutEx['parentesco'] ?? 'Madre'; ?>
                                        <option value="Madre" <?= $pEx === 'Madre' ? 'selected' : '' ?>>Madre</option>
                                        <option value="Padre" <?= $pEx === 'Padre' ? 'selected' : '' ?>>Padre</option>
                                        <option value="Tutor Legal" <?= $pEx === 'Tutor Legal' ? 'selected' : '' ?>>Tutor Legal</option>
                                        <option value="Abuelo/a" <?= $pEx === 'Abuelo/a' ? 'selected' : '' ?>>Abuelo/a</option>
                                        <option value="Tío/a" <?= $pEx === 'Tío/a' ? 'selected' : '' ?>>Tío/a</option>
                                        <option value="Hermano/a Mayor" <?= $pEx === 'Hermano/a Mayor' ? 'selected' : '' ?>>Hermano/a Mayor</option>
                                        <option value="Contacto Emergencia" <?= $pEx === 'Contacto Emergencia' ? 'selected' : '' ?>>Contacto Emergencia</option>
                                        <option value="Otro" <?= $pEx === 'Otro' ? 'selected' : '' ?>>Otro</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Teléfono Principal / WhatsApp <span class="req">*</span></label>
                                    <input type="tel" name="extra_tutor_telefono[]" value="<?= htmlspecialchars($tutEx['telefono']) ?>" required>
                                </div>
                            </div>

                            <div class="grid-3">
                                <div class="form-group">
                                    <label>Teléfono Secundario</label>
                                    <input type="tel" name="extra_tutor_telefono2[]" value="<?= htmlspecialchars($tutEx['telefono2'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Correo Electrónico</label>
                                    <input type="email" name="extra_tutor_correo[]" value="<?= htmlspecialchars($tutEx['correo'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Ocupación / Profesión</label>
                                    <input type="text" name="extra_tutor_ocupacion[]" value="<?= htmlspecialchars($tutEx['ocupacion'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="grid-3">
                                <div class="form-group">
                                    <label>Dirección Domiciliaria</label>
                                    <input type="text" name="extra_tutor_direccion[]" value="<?= htmlspecialchars($tutEx['direccion'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>¿Es Contacto de Emergencia?</label>
                                    <select name="extra_tutor_emergencia[]">
                                        <option value="1" <?= ($tutEx['es_contacto_emergencia'] ?? 1) == 1 ? 'selected' : '' ?>>Sí, contactar en emergencia</option>
                                        <option value="0" <?= ($tutEx['es_contacto_emergencia'] ?? 1) == 0 ? 'selected' : '' ?>>No prioritario</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Observaciones / Vínculo</label>
                                    <input type="text" name="extra_tutor_obs[]" value="<?= htmlspecialchars($tutEx['observaciones'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="btn-add-tutor" onclick="agregarTutorAdicional()">
                    ＋ Agregar Otro Apoderado, Madre, Padre o Contacto de Emergencia
                </button>
            </div>

            <!-- BOTONES -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span>💾 Guardar Cambios del Estudiante</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let extraTutorIndex = <?= count($tutoresExtras) ?>;

function agregarTutorAdicional() {
    extraTutorIndex++;
    const container = document.getElementById('tutores-adicionales-container');
    const div = document.createElement('div');
    div.className = 'tutor-block';
    div.id = 'extra-tutor-' + extraTutorIndex;
    div.innerHTML = `
        <div class="tutor-block-title">
            <span>👥 Apoderado / Familiar Adicional #${extraTutorIndex}</span>
        </div>
        <button type="button" class="btn-remove-tutor" onclick="document.getElementById('extra-tutor-${extraTutorIndex}').remove()">✕ Quitar</button>
        
        <div class="grid-3">
            <div class="form-group">
                <label>Nombre Completo <span class="req">*</span></label>
                <input type="text" name="extra_tutor_nombre[]" placeholder="Ej. Elena Choque de Flores" required>
            </div>

            <div class="form-group">
                <label>Parentesco <span class="req">*</span></label>
                <select name="extra_tutor_parentesco[]" required>
                    <option value="Madre">Madre</option>
                    <option value="Padre">Padre</option>
                    <option value="Tutor Legal">Tutor Legal</option>
                    <option value="Abuelo/a">Abuelo/a</option>
                    <option value="Tío/a">Tío/a</option>
                    <option value="Hermano/a Mayor">Hermano/a Mayor</option>
                    <option value="Contacto Emergencia">Contacto Emergencia</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>

            <div class="form-group">
                <label>Teléfono Principal / WhatsApp <span class="req">*</span></label>
                <input type="tel" name="extra_tutor_telefono[]" placeholder="Ej. 71829305" required>
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Teléfono Secundario</label>
                <input type="tel" name="extra_tutor_telefono2[]" placeholder="Ej. 22334455">
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="extra_tutor_correo[]" placeholder="familiar@gmail.com">
            </div>

            <div class="form-group">
                <label>Ocupación / Profesión</label>
                <input type="text" name="extra_tutor_ocupacion[]" placeholder="Ej. Arquitecta, Docente...">
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Dirección Domiciliaria</label>
                <input type="text" name="extra_tutor_direccion[]" placeholder="Ej. Misma dirección familiar">
            </div>

            <div class="form-group">
                <label>¿Es Contacto de Emergencia?</label>
                <select name="extra_tutor_emergencia[]">
                    <option value="1" selected>Sí, contactar en emergencia</option>
                    <option value="0">No prioritario</option>
                </select>
            </div>

            <div class="form-group">
                <label>Observaciones / Vínculo</label>
                <input type="text" name="extra_tutor_obs[]" placeholder="Ej. Disponible por las mañanas">
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>

</body>
</html>