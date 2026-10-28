<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos completos del docente
$sql = "SELECT d.*, 
               p.documento_identidad, 
               p.nombres, 
               p.apellidos, 
               p.fecha_nacimiento, 
               p.sexo, 
               p.telefono, 
               p.correo, 
               p.direccion,
               esp.nombre AS especialidad,
               esp.descripcion AS especialidad_desc,
               tc.nombre AS tipo_credencial,
               cd.titulo,
               cd.institucion_emisora,
               cd.estado_verificacion,
               cd.fecha_emision,
               cd.numero_documento
        FROM docentes d
        INNER JOIN personas p ON d.id_persona = p.id_persona
        LEFT JOIN docente_especialidad de ON d.id_persona = de.id_docente
        LEFT JOIN especialidades esp ON de.id_especialidad = esp.id_especialidad
        LEFT JOIN credenciales_docentes cd ON d.id_persona = cd.id_docente
        LEFT JOIN tipos_credencial tc ON cd.id_tipo_credencial = tc.id_tipo_credencial
        WHERE d.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Docente no encontrado. <a href='index.php'>Volver</a>");
}

// Calcular antigüedad y necesidad de renovación
$fechaContratacion = new DateTime($doc['fecha_contratacion']);
$hoy = new DateTime();
$antiguedad = $fechaContratacion->diff($hoy);
$anios = $antiguedad->y;
$meses = $antiguedad->m;
$textoAntiguedad = "{$anios} años y {$meses} meses";
if ($anios === 0) {
    $textoAntiguedad = "{$meses} meses";
}

$requiereRenovacion = ($anios >= 1 && strtoupper($doc['estado_laboral']) === 'ACTIVO');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Docente | <?= htmlspecialchars($doc['codigo_docente']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --card-bg: rgba(22, 29, 47, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #10b981;
            --primary-hover: #059669;
            --emerald: #10b981;
            --amber: #f59e0b;
            --rose: #ef4444;
            --field-bg: rgba(15, 23, 42, 0.6);
            --field-border: rgba(255, 255, 255, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 860px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        /* Profile Header */
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
            background: linear-gradient(135deg, #10b981, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            font-weight: 700;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .user-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .user-title p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-activo {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-licencia {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .status-retirado {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Renovation banner */
        .renovation-banner {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .renovation-text h4 {
            color: #fbbf24;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .renovation-text p {
            color: #d1d5db;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Detail grids */
        .section-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #34d399;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 14px;
            padding: 1rem 1.15rem;
        }

        .info-item span.label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
        }

        .info-item span.value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace !important;
            color: #6ee7b7 !important;
        }

        /* Card actions */
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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--emerald), #059669);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        .btn-renovar {
            background: linear-gradient(135deg, var(--amber), #d97706);
            color: #111827;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
        }
        .btn-renovar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
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
                    <?= !empty($doc['nombres']) ? strtoupper(mb_substr($doc['nombres'], 0, 1)) : 'D' ?>
                </div>
                <div class="user-title">
                    <h1><?= htmlspecialchars(trim($doc['nombres'] . ' ' . $doc['apellidos'])) ?></h1>
                    <p>
                        Docente de <strong style="color: #67e8f9;"><?= htmlspecialchars($doc['especialidad'] ?? 'Formación General') ?></strong> 
                        &bull; Código: <span class="code-font"><?= htmlspecialchars($doc['codigo_docente']) ?></span>
                    </p>
                </div>
            </div>

            <?php
                $est = strtoupper($doc['estado_laboral']);
                $clase = 'status-retirado';
                if ($est === 'ACTIVO') $clase = 'status-activo';
                elseif ($est === 'LICENCIA') $clase = 'status-licencia';
            ?>
            <span class="status-badge <?= $clase ?>">
                <?= htmlspecialchars($est) ?>
            </span>
        </div>

        <!-- Banner de Renovación si aplica -->
        <?php if ($requiereRenovacion): ?>
            <div class="renovation-banner">
                <div class="renovation-text">
                    <h4>⚠️ Contrato pendiente de renovación anual</h4>
                    <p>La última contratación registrada fue el <?= htmlspecialchars($doc['fecha_contratacion']) ?> (hace <?= $textoAntiguedad ?>). Se recomienda renovar la vigencia para la gestión actual.</p>
                </div>

                <form action="procesar.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="accion" value="renovar">
                    <input type="hidden" name="id_persona" value="<?= $doc['id_persona'] ?>">
                    <input type="hidden" name="fecha_contratacion" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="estado_laboral" value="ACTIVO">
                    <button type="submit" class="btn btn-renovar" onclick="return confirm('¿Confirmar renovación de contrato con fecha de hoy <?= date('d/m/Y') ?>?');">
                        🔄 Renovar Contrato Hoy
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Sección 1: Términos del Contrato Laboral -->
        <div class="section-label">📋 Información del Contrato Laboral</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Código de Contrato</span>
                <span class="value code-font"><?= htmlspecialchars($doc['codigo_docente']) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Fecha Inicio / Última Renovación</span>
                <span class="value"><?= htmlspecialchars($doc['fecha_contratacion']) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Antigüedad en la Institución</span>
                <span class="value" style="color: var(--emerald);"><?= htmlspecialchars($textoAntiguedad) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Estado de Contratación</span>
                <span class="value"><?= htmlspecialchars($doc['estado_laboral']) ?></span>
            </div>
        </div>

        <!-- Sección 2: Especialidad y Credenciales Académicas -->
        <div class="section-label">🎓 Especialidad y Currículum Profesional</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Especialidad Académica</span>
                <span class="value" style="color: #67e8f9;"><?= htmlspecialchars($doc['especialidad'] ?? 'No asignada') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Título Profesional Oficial</span>
                <span class="value"><?= htmlspecialchars($doc['titulo'] ?? 'En trámite / No especificado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Institución Emisora</span>
                <span class="value"><?= htmlspecialchars($doc['institucion_emisora'] ?? 'No registrada') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Estado de Verificación del Título</span>
                <span class="value" style="color: #34d399;">
                    <?= htmlspecialchars($doc['estado_verificacion'] ?? 'VERIFICADA') ?>
                </span>
            </div>
        </div>

        <!-- Sección 3: Datos de Identidad y Contacto -->
        <div class="section-label">👤 Datos Personales del Docente</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Documento de Identidad (CI)</span>
                <span class="value code-font"><?= htmlspecialchars($doc['documento_identidad'] ?? 'S/D') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Teléfono / WhatsApp</span>
                <span class="value"><?= htmlspecialchars($doc['telefono'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Correo Electrónico</span>
                <span class="value"><?= htmlspecialchars($doc['correo'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">ID de Persona en Sistema</span>
                <span class="value code-font">#<?= htmlspecialchars($doc['id_persona']) ?></span>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="card-actions">
            <a href="index.php" class="btn btn-secondary">
                <span>&larr; Volver a la Plana Docente</span>
            </a>

            <div style="display: flex; gap: 0.75rem;">
                <a href="editar.php?id=<?= $doc['id_persona'] ?>" class="btn btn-primary">
                    <span>✏️ Editar Ficha</span>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>