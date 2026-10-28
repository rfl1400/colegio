<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos completos del alumno
$sql = "SELECT e.*,
               p.documento_identidad,
               p.nombres,
               p.apellidos,
               p.fecha_nacimiento,
               p.sexo,
               p.direccion,
               p.telefono,
               p.correo,
               g.anio AS gestion,
               n.nombre AS nivel,
               gr.nombre AS grado,
               pa.nombre AS paralelo,
               o.aula,
               m.tipo_ingreso,
               m.estado AS estado_matricula,
               m.fecha_matricula
        FROM estudiantes e
        INNER JOIN personas p ON e.id_persona = p.id_persona
        LEFT JOIN matriculas m ON e.id_persona = m.id_estudiante
        LEFT JOIN ofertas_academicas o ON m.id_oferta = o.id_oferta
        LEFT JOIN gestiones g ON o.id_gestion = g.id_gestion
        LEFT JOIN grados gr ON o.id_grado = gr.id_grado
        LEFT JOIN niveles_academicos n ON gr.id_nivel = n.id_nivel
        LEFT JOIN paralelos pa ON o.id_paralelo = pa.id_paralelo
        WHERE e.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$est = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$est) {
    die("Estudiante no encontrado. <a href='index.php'>Volver al listado</a>");
}

// Cargar todos los contactos / apoderados registrados para este alumno
$sqlContactos = "SELECT * FROM contactos_estudiantes WHERE id_estudiante = :id ORDER BY es_representante DESC, id_contacto ASC";
$stmtCont = $conexion->prepare($sqlContactos);
$stmtCont->execute([':id' => $id]);
$contactos = $stmtCont->fetchAll(PDO::FETCH_ASSOC);

// Tutor titular principal (para enlace de hermano)
$tutorTitular = null;
foreach ($contactos as $c) {
    if ($c['es_representante'] == 1) {
        $tutorTitular = $c;
        break;
    }
}
if (!$tutorTitular && !empty($contactos)) {
    $tutorTitular = $contactos[0];
}

// Detectar hermanos en el colegio (comparten teléfono de contacto o apellido de familia)
$sqlHermanos = "SELECT DISTINCT e2.id_persona, e2.codigo_estudiante, p2.nombres, p2.apellidos,
                       gr.nombre AS grado, pa.nombre AS paralelo, n.nombre AS nivel
                FROM contactos_estudiantes c1
                JOIN contactos_estudiantes c2 ON (TRIM(c1.telefono) = TRIM(c2.telefono) AND c1.telefono != '') 
                     AND c1.id_estudiante != c2.id_estudiante
                JOIN estudiantes e2 ON c2.id_estudiante = e2.id_persona
                JOIN personas p2 ON e2.id_persona = p2.id_persona
                LEFT JOIN matriculas m2 ON e2.id_persona = m2.id_estudiante
                LEFT JOIN ofertas_academicas o2 ON m2.id_oferta = o2.id_oferta
                LEFT JOIN grados gr ON o2.id_grado = gr.id_grado
                LEFT JOIN niveles_academicos n ON gr.id_nivel = n.id_nivel
                LEFT JOIN paralelos pa ON o2.id_paralelo = pa.id_paralelo
                WHERE c1.id_estudiante = :id";
$stmtHerm = $conexion->prepare($sqlHermanos);
$stmtHerm->execute([':id' => $id]);
$hermanos = $stmtHerm->fetchAll(PDO::FETCH_ASSOC);

// Calcular edad del alumno
$edadTexto = 'No especificada';
if (!empty($est['fecha_nacimiento'])) {
    $fnac = new DateTime($est['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $fnac->diff($hoy);
    $edadTexto = "{$edad->y} años ({$edad->m} meses)";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha del Estudiante | <?= htmlspecialchars($est['codigo_estudiante']) ?></title>
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
            --rose: #ef4444;
            --purple: #8b5cf6;
            --field-bg: rgba(15, 23, 42, 0.65);
            --field-border: rgba(255, 255, 255, 0.08);
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

        .container {
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

        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--card-border);
            margin-bottom: 2rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .avatar {
            width: 68px;
            height: 68px;
            border-radius: 20px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.35);
        }

        .user-title h1 {
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .user-title p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-activa {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }
        .status-trasladada {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .status-retirada {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.35);
        }

        .section-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #60a5fa;
            margin: 1.5rem 0 1rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 14px;
            padding: 1rem 1.15rem;
        }

        .info-item span.label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
            font-weight: 600;
        }

        .info-item span.value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace !important;
            color: #93c5fd !important;
        }

        /* Parallel badge */
        .badge-parallel {
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-rojo {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-azul {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .badge-blanco {
            background: rgba(243, 244, 246, 0.15);
            color: #e5e7eb;
            border: 1px solid rgba(243, 244, 246, 0.3);
        }

        /* Sibling Card */
        .family-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
        }

        .family-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .sibling-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.85rem;
        }

        .sibling-item {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .sibling-item:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }

        .btn-add-sibling {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.45);
            color: #c7d2fe;
            padding: 0.45rem 0.9rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-add-sibling:hover {
            background: rgba(99, 102, 241, 0.35);
            color: #ffffff;
        }

        /* Contacts cards */
        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .contact-card {
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 16px;
            padding: 1.25rem;
            position: relative;
        }

        .contact-card.titular {
            border-color: rgba(59, 130, 246, 0.35);
            background: rgba(30, 41, 59, 0.5);
        }

        .contact-card-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 0.6rem;
        }

        .card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-main);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="user-meta">
                <div class="avatar">
                    <?= !empty($est['nombres']) ? strtoupper(mb_substr($est['nombres'], 0, 1)) : 'E' ?>
                </div>
                <div class="user-title">
                    <h1><?= htmlspecialchars(trim($est['nombres'] . ' ' . $est['apellidos'])) ?></h1>
                    <p>
                        Código: <span class="code-font"><?= htmlspecialchars($est['codigo_estudiante']) ?></span> &bull; 
                        Curso: <strong style="color: #93c5fd;"><?= htmlspecialchars($est['grado'] ?? 'Sin asignar') ?></strong>
                        <?php 
                        $par = $est['paralelo'] ?? '';
                        $parClass = 'badge-azul';
                        if ($par === 'Rojo') $parClass = 'badge-rojo';
                        elseif ($par === 'Blanco') $parClass = 'badge-blanco';
                        if ($par):
                        ?>
                            <span class="badge-parallel <?= $parClass ?>">Paralelo <?= htmlspecialchars($par) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php
                $estMat = strtoupper($est['estado_matricula'] ?? 'ACTIVA');
                $clase = 'status-retirada';
                if ($estMat === 'ACTIVA') $clase = 'status-activa';
                elseif ($estMat === 'TRASLADADA') $clase = 'status-trasladada';
            ?>
            <span class="status-badge <?= $clase ?>">
                <?= htmlspecialchars($estMat) ?>
            </span>
        </div>

        <!-- 1. Curso y Matrícula Actual -->
        <div class="section-label">🏫 1. Situación Académica y Matrícula (Gestión <?= htmlspecialchars($est['gestion'] ?? date('Y')) ?>)</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Curso / Grado</span>
                <span class="value" style="color: #93c5fd;">
                    <?= htmlspecialchars($est['grado'] ?? 'No asignado') ?>
                </span>
            </div>

            <div class="info-item">
                <span class="label">Paralelo y Aula</span>
                <span class="value">
                    Paralelo <?= htmlspecialchars($est['paralelo'] ?? 'S/P') ?> &bull; <?= htmlspecialchars($est['aula'] ?? 'Sin aula') ?>
                </span>
            </div>

            <div class="info-item">
                <span class="label">Nivel Académico</span>
                <span class="value"><?= htmlspecialchars($est['nivel'] ?? 'No asignado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Tipo de Ingreso y Fecha</span>
                <span class="value">
                    <?= htmlspecialchars($est['tipo_ingreso'] ?? 'REGULAR') ?> (<?= htmlspecialchars($est['fecha_matricula'] ?? $est['fecha_ingreso']) ?>)
                </span>
            </div>
        </div>

        <!-- 2. Datos de Identidad del Alumno -->
        <div class="section-label">👤 2. Datos de Identidad del Estudiante</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Documento de Identidad / RUDE</span>
                <span class="value code-font"><?= htmlspecialchars($est['documento_identidad'] ?? 'S/D') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Fecha de Nacimiento y Edad</span>
                <span class="value"><?= htmlspecialchars($est['fecha_nacimiento'] ?? '-') ?> &bull; <span style="color: #38bdf8;"><?= $edadTexto ?></span></span>
            </div>

            <div class="info-item">
                <span class="label">Sexo</span>
                <span class="value">
                    <?= $est['sexo'] === 'M' ? 'Masculino' : ($est['sexo'] === 'F' ? 'Femenino' : 'Otro') ?>
                </span>
            </div>

            <div class="info-item">
                <span class="label">Dirección Domiciliaria</span>
                <span class="value"><?= htmlspecialchars($est['direccion'] ?? 'No registrada') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Teléfono Propio del Estudiante</span>
                <span class="value"><?= htmlspecialchars($est['telefono'] ?? 'Sin teléfono directo') ?></span>
            </div>
        </div>

        <!-- TARJETA DE HERMANOS / FAMILIA EN EL COLEGIO -->
        <?php if (!empty($hermanos)): ?>
            <div class="family-card">
                <div class="family-header">
                    <div>
                        <strong style="color: #c7d2fe; font-size: 0.95rem;">👨‍👩‍👧‍👦 Hermanos en el Colegio (<?= count($hermanos) ?>)</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                            Estudiantes vinculados a la misma familia / número de contacto de apoderado:
                        </p>
                    </div>

                    <?php if ($tutorTitular): ?>
                        <a href="crear.php?apellidos=<?= urlencode($est['apellidos']) ?>&direccion=<?= urlencode($est['direccion'] ?? '') ?>&tutor_nombre=<?= urlencode($tutorTitular['nombre_completo']) ?>&tutor_tel=<?= urlencode($tutorTitular['telefono']) ?>&tutor_par=<?= urlencode($tutorTitular['parentesco']) ?>&tutor_tel2=<?= urlencode($tutorTitular['telefono2'] ?? '') ?>&tutor_email=<?= urlencode($tutorTitular['correo'] ?? '') ?>&tutor_ocup=<?= urlencode($tutorTitular['ocupacion'] ?? '') ?>" class="btn-add-sibling">
                            ＋ Inscribir Otro Hermano/a
                        </a>
                    <?php endif; ?>
                </div>

                <div class="sibling-list">
                    <?php foreach ($hermanos as $herm): 
                        $hPar = $herm['paralelo'] ?? '';
                        $hClass = 'badge-azul';
                        if ($hPar === 'Rojo') $hClass = 'badge-rojo';
                        elseif ($hPar === 'Blanco') $hClass = 'badge-blanco';
                    ?>
                        <a href="ver.php?id=<?= $herm['id_persona'] ?>" class="sibling-item">
                            <div>
                                <div style="font-weight: 700; color: #ffffff;"><?= htmlspecialchars($herm['nombres'] . ' ' . $herm['apellidos']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($herm['codigo_estudiante']) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.82rem; color: #93c5fd; font-weight: 600;"><?= htmlspecialchars($herm['grado'] ?? '') ?></div>
                                <?php if ($hPar): ?>
                                    <span class="badge-parallel <?= $hClass ?>" style="margin-top: 0.2rem;">Par. <?= htmlspecialchars($hPar) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <?php if ($tutorTitular): ?>
                <div style="text-align: right; margin-bottom: 1.5rem;">
                    <a href="crear.php?apellidos=<?= urlencode($est['apellidos']) ?>&direccion=<?= urlencode($est['direccion'] ?? '') ?>&tutor_nombre=<?= urlencode($tutorTitular['nombre_completo']) ?>&tutor_tel=<?= urlencode($tutorTitular['telefono']) ?>&tutor_par=<?= urlencode($tutorTitular['parentesco']) ?>" class="btn-add-sibling">
                        ＋ Inscribir Hermano/a con este Apoderado
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 3. Apoderados y Familiares -->
        <div class="section-label">
            <span>👨‍👩‍👧 3. Familiares, Apoderados y Contactos Registrados (<?= count($contactos) ?>)</span>
        </div>

        <div class="contacts-grid">
            <?php if (empty($contactos)): ?>
                <div class="info-item" style="grid-column: 1 / -1; color: var(--text-muted);">
                    No hay apoderados registrados para este estudiante.
                </div>
            <?php else: ?>
                <?php foreach ($contactos as $c): ?>
                    <div class="contact-card <?= $c['es_representante'] == 1 ? 'titular' : '' ?>">
                        <?php if ($c['es_representante'] == 1): ?>
                            <span class="contact-card-badge" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.4);">
                                👑 Apoderado Titular (Representante Legal)
                            </span>
                        <?php else: ?>
                            <span class="contact-card-badge" style="background: rgba(255, 255, 255, 0.08); color: #d1d5db; border: 1px solid rgba(255, 255, 255, 0.15);">
                                👥 Contacto / Familiar
                            </span>
                        <?php endif; ?>

                        <?php if ($c['es_contacto_emergencia'] == 1): ?>
                            <span class="contact-card-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
                                🚨 Emergencia
                            </span>
                        <?php endif; ?>

                        <div style="font-size: 1.05rem; font-weight: 700; color: #ffffff; margin-bottom: 0.35rem;">
                            <?= htmlspecialchars($c['nombre_completo']) ?>
                        </div>

                        <div style="font-size: 0.85rem; color: #93c5fd; margin-bottom: 0.75rem; font-weight: 600;">
                            Parentesco: <?= htmlspecialchars($c['parentesco'] ?? 'Familiar') ?>
                        </div>

                        <div style="font-size: 0.82rem; line-height: 1.7; color: #d1d5db;">
                            <div>📞 <strong>Principal:</strong> <?= htmlspecialchars($c['telefono']) ?></div>
                            <?php if (!empty($c['telefono2'])): ?>
                                <div>📱 <strong>Secundario:</strong> <?= htmlspecialchars($c['telefono2']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['correo'])): ?>
                                <div>✉️ <strong>Email:</strong> <?= htmlspecialchars($c['correo']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['ocupacion'])): ?>
                                <div>💼 <strong>Ocupación:</strong> <?= htmlspecialchars($c['ocupacion']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['direccion'])): ?>
                                <div>🏠 <strong>Dirección:</strong> <?= htmlspecialchars($c['direccion']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($c['observaciones'])): ?>
                                <div style="margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid rgba(255,255,255,0.06); color: var(--text-muted); font-style: italic;">
                                    📝 <?= htmlspecialchars($c['observaciones']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Observaciones Pedagógicas si existen -->
        <?php if (!empty($est['observaciones'])): ?>
            <div class="section-label">📝 Observaciones Médicas / Pedagógicas / Procedencia</div>
            <div class="info-item" style="margin-bottom: 2rem;">
                <p style="color: #e5e7eb; font-size: 0.9rem; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($est['observaciones'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="card-actions">
            <a href="index.php" class="btn btn-secondary">
                <span>&larr; Volver al Listado de Estudiantes</span>
            </a>

            <div style="display: flex; gap: 0.75rem;">
                <a href="editar.php?id=<?= $est['id_persona'] ?>" class="btn btn-primary">
                    <span>✏️ Editar Ficha del Estudiante</span>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>