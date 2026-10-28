<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener detalles completos mediante LEFT JOIN con personas
$sql = "SELECT a.*, 
               p.documento_identidad, 
               p.nombres, 
               p.apellidos, 
               p.fecha_nacimiento, 
               p.sexo, 
               p.telefono, 
               p.correo, 
               p.direccion, 
               p.estado AS estado_persona
        FROM administrativos a
        LEFT JOIN personas p ON a.id_persona = p.id_persona
        WHERE a.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adm) {
    die("Personal administrativo no encontrado. <a href='index.php'>Volver</a>");
}

// Calcular antigüedad laboral
$fechaContratacion = new DateTime($adm['fecha_contratacion']);
$hoy = new DateTime();
$antiguedad = $fechaContratacion->diff($hoy);
$anios = $antiguedad->y;
$meses = $antiguedad->m;
$textoAntiguedad = "{$anios} años y {$meses} meses";
if ($anios === 0) {
    $textoAntiguedad = "{$meses} meses";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Administrativa | <?= htmlspecialchars($adm['codigo_administrativo']) ?></title>
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
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --emerald: #10b981;
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
            max-width: 800px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        /* Profile header */
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
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .user-title h1 {
            font-size: 1.45rem;
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
        .status-inactivo {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .status-licencia {
            background: rgba(245, 158, 11, 0.12);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Detail grids */
        .section-label {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #818cf8;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            font-family: inherit;
        }

        .code-font {
            font-family: 'JetBrains Mono', monospace !important;
            color: #a5b4fc !important;
        }

        /* Actions */
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
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
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
                    <?= !empty($adm['nombres']) ? strtoupper(mb_substr($adm['nombres'], 0, 1)) : 'A' ?>
                </div>
                <div class="user-title">
                    <h1>
                        <?php if (!empty($adm['nombres']) || !empty($adm['apellidos'])): ?>
                            <?= htmlspecialchars(trim(($adm['nombres'] ?? '') . ' ' . ($adm['apellidos'] ?? ''))) ?>
                        <?php else: ?>
                            Administrativo #<?= $adm['id_persona'] ?>
                        <?php endif; ?>
                    </h1>
                    <p><?= htmlspecialchars($adm['cargo']) ?> &bull; Código: <span class="code-font"><?= htmlspecialchars($adm['codigo_administrativo']) ?></span></p>
                </div>
            </div>

            <?php
                $estado = strtolower($adm['estado_laboral']);
                $clase = 'status-inactivo';
                if ($estado === 'activo') $clase = 'status-activo';
                elseif ($estado === 'licencia') $clase = 'status-licencia';
            ?>
            <span class="status-badge <?= $clase ?>">
                <?= htmlspecialchars(ucfirst($adm['estado_laboral'])) ?>
            </span>
        </div>

        <!-- Sección 1: Datos Laborales -->
        <div class="section-label">📋 Información Institucional</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Código Administrativo</span>
                <span class="value code-font"><?= htmlspecialchars($adm['codigo_administrativo']) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Cargo Oficial</span>
                <span class="value"><?= htmlspecialchars($adm['cargo']) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Fecha de Contratación</span>
                <span class="value"><?= htmlspecialchars($adm['fecha_contratacion']) ?></span>
            </div>

            <div class="info-item">
                <span class="label">Antigüedad en la Institución</span>
                <span class="value" style="color: var(--emerald);"><?= htmlspecialchars($textoAntiguedad) ?></span>
            </div>
        </div>

        <!-- Sección 2: Datos Personales (Tabla: personas) -->
        <div class="section-label">👤 Datos de Identidad y Contacto</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Documento de Identidad (CI)</span>
                <span class="value code-font"><?= htmlspecialchars($adm['documento_identidad'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Teléfono / Celular</span>
                <span class="value"><?= htmlspecialchars($adm['telefono'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">Correo Electrónico</span>
                <span class="value"><?= htmlspecialchars($adm['correo'] ?? 'No registrado') ?></span>
            </div>

            <div class="info-item">
                <span class="label">ID de Registro en Base de Datos</span>
                <span class="value code-font">#<?= htmlspecialchars($adm['id_persona']) ?></span>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="card-actions">
            <a href="index.php" class="btn btn-secondary">
                <span>&larr; Volver al Listado</span>
            </a>

            <div style="display: flex; gap: 0.75rem;">
                <a href="editar.php?id=<?= $adm['id_persona'] ?>" class="btn btn-primary">
                    <span>✏️ Editar Información</span>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>