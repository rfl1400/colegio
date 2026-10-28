<?php
require_once '../conexion.php';

// Obtener todas las ofertas activas agrupadas por nivel
$sqlOfertas = "SELECT o.id_oferta, g.anio AS gestion, gr.nombre AS grado, pa.nombre AS paralelo, n.nombre AS nivel, o.aula
               FROM ofertas_academicas o
               JOIN gestiones g ON o.id_gestion = g.id_gestion
               JOIN grados gr ON o.id_grado = gr.id_grado
               JOIN niveles_academicos n ON gr.id_nivel = n.id_nivel
               JOIN paralelos pa ON o.id_paralelo = pa.id_paralelo
               WHERE g.estado = 'ACTIVA'
               ORDER BY n.orden ASC, gr.id_grado ASC, pa.nombre ASC";
$ofertasRaw = $conexion->query($sqlOfertas)->fetchAll(PDO::FETCH_ASSOC);

// Agrupar ofertas por nivel
$ofertasPorNivel = [];
foreach ($ofertasRaw as $of) {
    $nivel = $of['nivel'];
    if (!isset($ofertasPorNivel[$nivel])) {
        $ofertasPorNivel[$nivel] = [];
    }
    $ofertasPorNivel[$nivel][] = $of;
}

// Obtener lista de tutores/apoderados ya registrados para asociar hermanos
$sqlTutores = "SELECT DISTINCT c.nombre_completo, c.parentesco, c.telefono, c.telefono2, c.correo, c.ocupacion, c.direccion,
                      p.nombres AS hijo_nombres, p.apellidos AS hijo_apellidos,
                      gr.nombre AS hijo_grado, pa.nombre AS hijo_paralelo
               FROM contactos_estudiantes c
               JOIN personas p ON c.id_estudiante = p.id_persona
               LEFT JOIN matriculas m ON p.id_persona = m.id_estudiante
               LEFT JOIN ofertas_academicas o ON m.id_oferta = o.id_oferta
               LEFT JOIN grados gr ON o.id_grado = gr.id_grado
               LEFT JOIN paralelos pa ON o.id_paralelo = pa.id_paralelo
               WHERE c.es_representante = 1
               ORDER BY c.nombre_completo ASC";
$tutoresExistentes = $conexion->query($sqlTutores)->fetchAll(PDO::FETCH_ASSOC);

// Generar siguiente código estudiante correlativo
$siguienteCodigo = 'EST-' . date('Y') . '-001';
try {
    $ultimo = $conexion->query("SELECT codigo_estudiante FROM estudiantes ORDER BY id_persona DESC LIMIT 1")->fetchColumn();
    if ($ultimo && preg_match('/(\d+)$/', $ultimo, $coincidencias)) {
        $num = intval($coincidencias[1]) + 1;
        $siguienteCodigo = 'EST-' . date('Y') . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
} catch (Exception $e) {}

// Si viene pre-cargado un tutor o familia por GET (ej. al hacer clic en "+ Inscribir Hermano")
$apellidosPre    = $_GET['apellidos'] ?? '';
$direccionPre    = $_GET['direccion'] ?? '';
$tutorPreNombre  = $_GET['tutor_nombre'] ?? '';
$tutorPreTel     = $_GET['tutor_tel'] ?? '';
$tutorPrePar     = $_GET['tutor_par'] ?? 'Padre';
$tutorPreTel2    = $_GET['tutor_tel2'] ?? '';
$tutorPreEmail   = $_GET['tutor_email'] ?? '';
$tutorPreOcup    = $_GET['tutor_ocup'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matricular Nuevo Estudiante | Colegio Pedagógico</title>
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
            --emerald: #10b981;
            --amber: #f59e0b;
            --purple: #8b5cf6;
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

        /* Secciones del formulario */
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

        /* Grids ordenados */
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

        .grid-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1.15rem;
        }

        @media (max-width: 820px) {
            .grid-3, .grid-4 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .grid-2, .grid-3, .grid-4 {
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

        /* Caja de reutilización / hermanos */
        .tutor-family-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.09), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 14px;
            padding: 1.15rem 1.25rem;
            margin-bottom: 1.25rem;
        }

        .tutor-family-box label {
            color: #c7d2fe;
            font-size: 0.82rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Tarjeta de tutor */
        .tutor-block {
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.2s ease;
        }

        .tutor-block:hover {
            border-color: rgba(59, 130, 246, 0.35);
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
            transform: translateY(-1px);
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

        /* Acciones inferiores */
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
                <div class="icon-badge">🎒</div>
                <div>
                    <h1>Matricular Nuevo Estudiante</h1>
                    <p>Primaria y Secundaria &bull; Paralelos Rojo, Azul, Blanco &bull; Vínculo de Familia y Apoderados</p>
                </div>
            </div>
            <a href="index.php" class="btn-back-header">
                ← Volver al Listado
            </a>
        </header>

        <form action="procesar.php" method="POST" id="form-estudiante">
            
            <!-- SECCIÓN 1: DATOS PERSONALES DEL ESTUDIANTE -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">1</span>
                    <span>👤 Información Personal del Estudiante</span>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombres del Estudiante <span class="req">*</span></label>
                        <input type="text" name="nombres" placeholder="Ej. Mateo Daniel" required autofocus>
                    </div>

                    <div class="form-group">
                        <label>Apellidos del Estudiante <span class="req">*</span></label>
                        <input type="text" name="apellidos" value="<?= htmlspecialchars($apellidosPre) ?>" placeholder="Ej. Flores Choque" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>C.I. / RUDE Escolar <span class="req">*</span></label>
                        <input type="text" name="documento_identidad" placeholder="Ej. 10293847-LP" required>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Nacimiento <span class="req">*</span></label>
                        <input type="date" name="fecha_nacimiento" required>
                    </div>

                    <div class="form-group">
                        <label>Sexo <span class="req">*</span></label>
                        <select name="sexo" required>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="OTRO">Otro</option>
                            <option value="NO_ESPECIFICA">Prefiero no especificar</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Dirección Domiciliaria</label>
                        <input type="text" name="direccion" value="<?= htmlspecialchars($direccionPre) ?>" placeholder="Ej. Av. 6 de Agosto #2435, Sopocachi">
                    </div>

                    <div class="form-group">
                        <label>Teléfono Propio del Estudiante (opcional)</label>
                        <input type="tel" name="telefono" placeholder="Ej. 71829304">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: ASIGNACIÓN ACADÉMICA Y MATRÍCULA -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">2</span>
                    <span>🏫 Asignación Escolar, Grado y Paralelo (Gestión 2026)</span>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Código Estudiantil <span class="req">*</span></label>
                        <input type="text" name="codigo_estudiante" value="<?= htmlspecialchars($siguienteCodigo) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #93c5fd;">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Curso, Nivel y Paralelo Asignado <span class="req">*</span></label>
                        <select name="id_oferta" required>
                            <option value="">-- Seleccione Nivel, Grado y Paralelo --</option>
                            <?php foreach ($ofertasPorNivel as $nivelNombre => $lista): ?>
                                <optgroup label="📚 NIVEL <?= strtoupper(htmlspecialchars($nivelNombre)) ?>">
                                    <?php foreach ($lista as $of): 
                                        $colorIcon = ($of['paralelo'] === 'Rojo') ? '🔴' : (($of['paralelo'] === 'Azul') ? '🔵' : '⚪');
                                    ?>
                                        <option value="<?= $of['id_oferta'] ?>">
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
                        <select name="tipo_ingreso" id="tipo_ingreso" onchange="toggleColegioOrigen()" required>
                            <option value="NUEVO" selected>NUEVO (Primer Ingreso)</option>
                            <option value="REGULAR">REGULAR (Continuidad)</option>
                            <option value="TRASLADO">TRASLADO (De otra institución)</option>
                            <option value="REINGRESO">REINGRESO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha de Matrícula <span class="req">*</span></label>
                        <input type="date" name="fecha_ingreso" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Estado de Matrícula <span class="req">*</span></label>
                        <select name="estado_matricula" required>
                            <option value="ACTIVA" selected>ACTIVA</option>
                            <option value="TRASLADADA">TRASLADADA</option>
                            <option value="RETIRADA">RETIRADA</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="grupo_colegio_origen" style="display: none;">
                    <label>Institución Educativa de Procedencia (Colegio Anterior)</label>
                    <input type="text" name="colegio_origen" placeholder="Ej. Colegio San Calixto, La Paz / Escuela España...">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Observaciones Médicas, Pedagógicas o de Matrícula</label>
                    <textarea name="observaciones" rows="2" placeholder="Alergias, condiciones especiales, seguro de salud, becas, notas de disciplina..."></textarea>
                </div>
            </div>

            <!-- SECCIÓN 3: FAMILIA Y APODERADOS -->
            <div class="section-box">
                <div class="section-header">
                    <span class="section-badge">3</span>
                    <span>👨‍👩‍👧‍👦 Familiares, Apoderados y Vínculo de Hermanos</span>
                </div>

                <!-- Detector de hermanos / Reutilización de apoderado existente -->
                <div class="tutor-family-box">
                    <label>
                        💡 ¿El estudiante tiene un hermano/a ya registrado en el colegio?
                    </label>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.4rem 0 0.75rem 0;">
                        Si la madre, padre o apoderado ya existe, selecciónalo aquí para autocompletar sus datos y asociar automáticamente a los hermanos:
                    </p>
                    <select id="select-tutor-existente" onchange="autocompletarTutor()" style="border-color: #6366f1;">
                        <option value="">-- Inscribir con Tutor Nuevo (o seleccionar abajo para reutilizar datos de familia) --</option>
                        <?php foreach ($tutoresExistentes as $tut): ?>
                            <option value="<?= htmlspecialchars($tut['nombre_completo']) ?>" 
                                    data-parentesco="<?= htmlspecialchars($tut['parentesco']) ?>"
                                    data-telefono="<?= htmlspecialchars($tut['telefono']) ?>"
                                    data-telefono2="<?= htmlspecialchars($tut['telefono2'] ?? '') ?>"
                                    data-correo="<?= htmlspecialchars($tut['correo'] ?? '') ?>"
                                    data-ocupacion="<?= htmlspecialchars($tut['ocupacion'] ?? '') ?>"
                                    data-direccion="<?= htmlspecialchars($tut['direccion'] ?? '') ?>">
                                <?= htmlspecialchars($tut['nombre_completo']) ?> (<?= htmlspecialchars($tut['parentesco']) ?> de: <?= htmlspecialchars($tut['hijo_nombres']) ?> <?= htmlspecialchars($tut['hijo_apellidos']) ?> en <?= htmlspecialchars($tut['hijo_grado'] ?? 'Colegio') ?> <?= htmlspecialchars($tut['hijo_paralelo'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- TUTOR TITULAR (Representante Legal Principal) -->
                <div class="tutor-block" id="tutor-titular">
                    <div class="tutor-block-title">
                        <span>👤 Apoderado Titular (Representante Legal Principal)</span>
                        <span style="color: #ef4444; font-size: 0.8rem;">* Requerido</span>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Nombre Completo del Apoderado <span class="req">*</span></label>
                            <input type="text" name="tutor_nombre" id="tutor_nombre" value="<?= htmlspecialchars($tutorPreNombre) ?>" placeholder="Ej. Gabriel Flores Mamani" required>
                        </div>

                        <div class="form-group">
                            <label>Parentesco <span class="req">*</span></label>
                            <select name="tutor_parentesco" id="tutor_parentesco" required>
                                <option value="Padre" <?= $tutorPrePar === 'Padre' ? 'selected' : '' ?>>Padre</option>
                                <option value="Madre" <?= $tutorPrePar === 'Madre' ? 'selected' : '' ?>>Madre</option>
                                <option value="Tutor Legal" <?= $tutorPrePar === 'Tutor Legal' ? 'selected' : '' ?>>Tutor Legal</option>
                                <option value="Abuelo/a" <?= $tutorPrePar === 'Abuelo/a' ? 'selected' : '' ?>>Abuelo/a</option>
                                <option value="Tío/a" <?= $tutorPrePar === 'Tío/a' ? 'selected' : '' ?>>Tío/a</option>
                                <option value="Hermano/a Mayor" <?= $tutorPrePar === 'Hermano/a Mayor' ? 'selected' : '' ?>>Hermano/a Mayor</option>
                                <option value="Otro" <?= $tutorPrePar === 'Otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teléfono Principal / WhatsApp <span class="req">*</span></label>
                            <input type="tel" name="tutor_telefono" id="tutor_telefono" value="<?= htmlspecialchars($tutorPreTel) ?>" placeholder="Ej. 71829304" required>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Teléfono Secundario / Fijo</label>
                            <input type="tel" name="tutor_telefono2" id="tutor_telefono2" value="<?= htmlspecialchars($tutorPreTel2) ?>" placeholder="Ej. 22441122">
                        </div>

                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="tutor_correo" id="tutor_correo" value="<?= htmlspecialchars($tutorPreEmail) ?>" placeholder="apoderado@gmail.com">
                        </div>

                        <div class="form-group">
                            <label>Ocupación / Profesión</label>
                            <input type="text" name="tutor_ocupacion" id="tutor_ocupacion" value="<?= htmlspecialchars($tutorPreOcup) ?>" placeholder="Ej. Contador, Ingeniero, Comerciante...">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Dirección del Apoderado</label>
                            <input type="text" name="tutor_direccion" id="tutor_direccion" placeholder="Ej. Misma dirección del alumno">
                        </div>

                        <div class="form-group">
                            <label>Observaciones / Disponibilidad</label>
                            <input type="text" name="tutor_obs" id="tutor_obs" placeholder="Ej. Llamar preferentemente por la tarde">
                        </div>
                    </div>
                </div>

                <!-- CONTENEDOR DE APODERADOS / TUTORES ADICIONALES -->
                <div id="tutores-adicionales-container"></div>

                <button type="button" class="btn-add-tutor" onclick="agregarTutorAdicional()">
                    ＋ Agregar Otro Apoderado, Madre, Padre o Contacto de Emergencia
                </button>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span>💾 Guardar y Matricular Estudiante</span>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
let extraTutorIndex = 0;

function autocompletarTutor() {
    const select = document.getElementById('select-tutor-existente');
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption.value) return;

    document.getElementById('tutor_nombre').value = selectedOption.value;
    document.getElementById('tutor_telefono').value = selectedOption.getAttribute('data-telefono') || '';
    document.getElementById('tutor_telefono2').value = selectedOption.getAttribute('data-telefono2') || '';
    document.getElementById('tutor_correo').value = selectedOption.getAttribute('data-correo') || '';
    document.getElementById('tutor_ocupacion').value = selectedOption.getAttribute('data-ocupacion') || '';
    document.getElementById('tutor_direccion').value = selectedOption.getAttribute('data-direccion') || '';
    
    const parentesco = selectedOption.getAttribute('data-parentesco');
    const selectParentesco = document.getElementById('tutor_parentesco');
    if (parentesco) {
        selectParentesco.value = parentesco;
    }
}

function toggleColegioOrigen() {
    const tipo = document.getElementById('tipo_ingreso').value;
    const box = document.getElementById('grupo_colegio_origen');
    if (tipo === 'TRASLADO') {
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

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