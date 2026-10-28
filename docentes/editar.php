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
               de.id_especialidad,
               cd.id_tipo_credencial,
               cd.titulo,
               cd.institucion_emisora
        FROM docentes d
        INNER JOIN personas p ON d.id_persona = p.id_persona
        LEFT JOIN docente_especialidad de ON d.id_persona = de.id_docente
        LEFT JOIN credenciales_docentes cd ON d.id_persona = cd.id_docente
        WHERE d.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    die("Docente no encontrado. <a href='index.php'>Volver</a>");
}

// Catálogos
$especialidades = $conexion->query("SELECT id_especialidad, nombre FROM especialidades ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiposCredencial = $conexion->query("SELECT id_tipo_credencial, nombre FROM tipos_credencial ORDER BY id_tipo_credencial ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Docente | <?= htmlspecialchars($doc['codigo_docente']) ?></title>
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
            --field-bg: rgba(15, 23, 42, 0.6);
            --field-border: rgba(255, 255, 255, 0.1);
            --field-focus: #34d399;
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

        .form-container {
            width: 100%;
            max-width: 820px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-badge {
            width: 50px;
            height: 50px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .header h1 {
            font-size: 1.55rem;
            font-weight: 800;
        }

        .header p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .section-subtitle {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #34d399;
            margin: 1.75rem 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.25rem;
        }

        @media (max-width: 640px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 0.4rem;
        }

        input[type="text"],
        input[type="date"],
        input[type="email"],
        select {
            width: 100%;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input:focus, select:focus {
            border-color: var(--field-focus);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        select option {
            background: #111827;
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
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
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

        .btn-renovar-quick {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-renovar-quick:hover {
            background: #f59e0b;
            color: #111827;
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
                    <h1>Editar Ficha del Docente</h1>
                    <p>Código: <strong style="color: #6ee7b7; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($doc['codigo_docente']) ?></strong> &bull; ID #<?= $doc['id_persona'] ?></p>
                </div>
            </div>

            <button type="button" onclick="document.getElementById('input-fecha-contrato').value = '<?= date('Y-m-d') ?>'; document.getElementById('select-estado').value = 'ACTIVO';" class="btn-renovar-quick">
                🔄 Fijar Renovación a Hoy
            </button>
        </header>

        <form action="procesar.php" method="POST">
            <input type="hidden" name="id_persona" value="<?= htmlspecialchars($doc['id_persona']) ?>">

            <!-- 1. Datos Personales -->
            <div class="section-subtitle">
                <span>👤 1. Datos Personales y de Contacto</span>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Nombres</label>
                    <input type="text" name="nombres" value="<?= htmlspecialchars($doc['nombres']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" value="<?= htmlspecialchars($doc['apellidos']) ?>" required>
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Documento de Identidad (CI)</label>
                    <input type="text" name="documento_identidad" value="<?= htmlspecialchars($doc['documento_identidad'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($doc['telefono'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" value="<?= htmlspecialchars($doc['correo'] ?? '') ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($doc['fecha_nacimiento'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo">
                        <option value="NO_ESPECIFICA" <?= $doc['sexo'] === 'NO_ESPECIFICA' ? 'selected' : '' ?>>No especifica</option>
                        <option value="F" <?= $doc['sexo'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                        <option value="M" <?= $doc['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="OTRO" <?= $doc['sexo'] === 'OTRO' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
            </div>

            <!-- 2. Contrato y Situación Laboral -->
            <div class="section-subtitle">
                <span>📋 2. Términos del Contrato y Renovación</span>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Código Docente</label>
                    <input type="text" name="codigo_docente" value="<?= htmlspecialchars($doc['codigo_docente']) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace;">
                </div>

                <div class="form-group">
                    <label>Fecha de Contratación / Renovación</label>
                    <input type="date" name="fecha_contratacion" id="input-fecha-contrato" value="<?= htmlspecialchars($doc['fecha_contratacion']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Estado Laboral</label>
                    <select name="estado_laboral" id="select-estado" required>
                        <option value="ACTIVO" <?= strtoupper($doc['estado_laboral']) === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO (Vigente)</option>
                        <option value="LICENCIA" <?= strtoupper($doc['estado_laboral']) === 'LICENCIA' ? 'selected' : '' ?>>LICENCIA (Temporal)</option>
                        <option value="RETIRADO" <?= strtoupper($doc['estado_laboral']) === 'RETIRADO' ? 'selected' : '' ?>>RETIRADO</option>
                    </select>
                </div>
            </div>

            <!-- 3. Especialidad y Credenciales -->
            <div class="section-subtitle">
                <span>🎓 3. Formación Académica y Especialidad</span>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Especialidad Principal Asignada</label>
                    <select name="id_especialidad">
                        <option value="">-- Sin Especialidad Asignada --</option>
                        <?php foreach ($especialidades as $esp): ?>
                            <option value="<?= $esp['id_especialidad'] ?>" <?= $doc['id_especialidad'] == $esp['id_especialidad'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($esp['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Grado / Nivel de Credencial</label>
                    <select name="id_tipo_credencial">
                        <?php foreach ($tiposCredencial as $tc): ?>
                            <option value="<?= $tc['id_tipo_credencial'] ?>" <?= $doc['id_tipo_credencial'] == $tc['id_tipo_credencial'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tc['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Título Profesional Oficial</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($doc['titulo'] ?? '') ?>" placeholder="Ej. Licenciatura en Educación">
                </div>

                <div class="form-group">
                    <label>Institución Emisora</label>
                    <input type="text" name="institucion_emisora" value="<?= htmlspecialchars($doc['institucion_emisora'] ?? '') ?>" placeholder="Ej. ESFM Simón Bolívar">
                </div>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span>Guardar Cambios</span>
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>