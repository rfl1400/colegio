<?php
require_once '../conexion.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos del administrativo y su persona asociada
$sql = "SELECT a.*,
               p.documento_identidad,
               p.nombres,
               p.apellidos,
               p.fecha_nacimiento,
               p.sexo,
               p.estado_civil,
               p.telefono,
               p.telefono2,
               p.correo,
               p.direccion,
               p.ocupacion
        FROM administrativos a
        LEFT JOIN personas p ON a.id_persona = p.id_persona
        WHERE a.id_persona = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([':id' => $id]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adm) {
    die("Personal administrativo no encontrado. <a href='index.php'>Volver</a>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Administrativo | <?= htmlspecialchars($adm['codigo_administrativo']) ?></title>
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
            --primary: #f59e0b;
            --primary-hover: #d97706;
            --field-bg: rgba(15, 23, 42, 0.6);
            --field-border: rgba(255, 255, 255, 0.1);
            --field-focus: #fbbf24;
            --section-color: #fbbf24;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
        }

        .form-container { width: 100%; max-width: 920px; margin: 0 auto; }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
        }

        .header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); }

        .icon-badge {
            width: 52px; height: 52px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; flex-shrink: 0;
        }

        .header h1 { font-size: 1.5rem; font-weight: 800; }
        .header p { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem; }

        .section-subtitle {
            font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--section-color); margin: 2rem 0 1.1rem 0;
            display: flex; align-items: center; gap: 0.5rem;
            padding-bottom: 0.5rem; border-bottom: 1px solid rgba(245,158,11,0.15);
        }

        .section-box {
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 0.5rem;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.1rem; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1.1rem; }

        @media (max-width: 720px) { .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .grid-4 { grid-template-columns: 1fr 1fr; } .grid-3 { grid-template-columns: 1fr 1fr; } }

        .form-group { margin-bottom: 1rem; }

        label { display: block; font-size: 0.78rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.4rem; }
        label span.req { color: #ef4444; }

        input[type="text"],
        input[type="date"],
        input[type="email"],
        input[type="number"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 10px;
            padding: 0.65rem 0.9rem;
            font-size: 0.875rem;
            color: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--field-focus);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.2);
        }

        select option { background: #111827; color: #ffffff; }
        textarea { resize: vertical; min-height: 70px; }

        .form-actions {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem; margin-top: 2rem;
            padding-top: 1.5rem; border-top: 1px solid var(--card-border);
        }

        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.4rem; border-radius: 12px; font-size: 0.875rem;
            font-weight: 600; text-decoration: none; transition: all 0.2s ease;
            cursor: pointer; border: none; font-family: inherit;
        }

        .btn-primary { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; box-shadow: 0 4px 14px rgba(245,158,11,0.4); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(245,158,11,0.55); }
        .btn-cancel { background: rgba(255,255,255,0.05); border: 1px solid var(--card-border); color: var(--text-muted); }
        .btn-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .hint { font-size: 0.73rem; color: var(--text-muted); margin-top: 0.3rem; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="card">
        <header class="header">
            <div class="icon-badge">✏️</div>
            <div>
                <h1>Editar Personal Administrativo</h1>
                <p>Código: <strong style="color: #fbbf24; font-family: 'JetBrains Mono', monospace;"><?= htmlspecialchars($adm['codigo_administrativo']) ?></strong> &bull; ID: <?= $adm['id_persona'] ?></p>
            </div>
        </header>

        <form action="procesar.php" method="POST">
            <input type="hidden" name="id_persona" value="<?= htmlspecialchars($adm['id_persona']) ?>">

            <!-- 1. Identidad Personal -->
            <div class="section-subtitle">👤 1. Datos de Identidad Personal</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nombres <span class="req">*</span></label>
                        <input type="text" name="nombres" value="<?= htmlspecialchars($adm['nombres'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos <span class="req">*</span></label>
                        <input type="text" name="apellidos" value="<?= htmlspecialchars($adm['apellidos'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Documento de Identidad (CI)</label>
                        <input type="text" name="documento_identidad" value="<?= htmlspecialchars($adm['documento_identidad'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($adm['fecha_nacimiento'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Sexo</label>
                        <select name="sexo">
                            <option value="NO_ESPECIFICA" <?= ($adm['sexo'] ?? '') === 'NO_ESPECIFICA' ? 'selected' : '' ?>>No especifica</option>
                            <option value="F" <?= ($adm['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                            <option value="M" <?= ($adm['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="OTRO" <?= ($adm['sexo'] ?? '') === 'OTRO' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <select name="estado_civil">
                            <option value="">-- No especificado --</option>
                            <?php foreach (['SOLTERO'=>'Soltero/a','CASADO'=>'Casado/a','DIVORCIADO'=>'Divorciado/a','VIUDO'=>'Viudo/a','UNION_LIBRE'=>'Unión Libre'] as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($adm['estado_civil'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ocupación / Profesión</label>
                        <input type="text" name="ocupacion" value="<?= htmlspecialchars($adm['ocupacion'] ?? '') ?>" placeholder="Ej. Contador Público, Abogado...">
                    </div>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label>Teléfono Principal</label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($adm['telefono'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono Secundario</label>
                        <input type="tel" name="telefono2" value="<?= htmlspecialchars($adm['telefono2'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" value="<?= htmlspecialchars($adm['correo'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dirección Domiciliaria</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($adm['direccion'] ?? '') ?>">
                </div>
            </div>

            <!-- 2. Datos Laborales -->
            <div class="section-subtitle">📋 2. Cargo y Contratación Institucional</div>
            <div class="section-box">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Código Administrativo <span class="req">*</span></label>
                        <input type="text" name="codigo_administrativo" value="<?= htmlspecialchars($adm['codigo_administrativo']) ?>" maxlength="30" required style="font-family: 'JetBrains Mono', monospace; color: #fbbf24;">
                    </div>
                    <div class="form-group">
                        <label>Cargo Asignado <span class="req">*</span></label>
                        <input type="text" name="cargo" list="lista-cargos" value="<?= htmlspecialchars($adm['cargo']) ?>" maxlength="100" required>
                        <datalist id="lista-cargos">
                            <option value="Director General"><option value="Subdirector Académico">
                            <option value="Secretaria Académica"><option value="Contador General">
                            <option value="Auxiliar Contable"><option value="Regente Escolar">
                            <option value="Encargada de Biblioteca"><option value="Administrador de Sistemas / TIC">
                            <option value="Psicopedagogo/a Institucional"><option value="Jefe de Recursos Humanos">
                            <option value="Auxiliar de Mantenimiento"><option value="Portero / Vigilancia">
                        </datalist>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Departamento / Área</label>
                        <input type="text" name="departamento" list="lista-depts" value="<?= htmlspecialchars($adm['departamento'] ?? '') ?>">
                        <datalist id="lista-depts">
                            <option value="Dirección General"><option value="Secretaría Académica">
                            <option value="Administración y Finanzas"><option value="Recursos Humanos">
                            <option value="Biblioteca"><option value="Sistemas TIC">
                            <option value="Orientación Psicopedagógica"><option value="Mantenimiento y Servicios">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label>Turno de Trabajo</label>
                        <select name="turno">
                            <option value="MANANA" <?= ($adm['turno'] ?? '') === 'MANANA' ? 'selected' : '' ?>>Mañana</option>
                            <option value="TARDE" <?= ($adm['turno'] ?? '') === 'TARDE' ? 'selected' : '' ?>>Tarde</option>
                            <option value="COMPLETO" <?= ($adm['turno'] ?? '') === 'COMPLETO' ? 'selected' : '' ?>>Completo (Doble)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Salario Mensual (Bs.)</label>
                        <input type="number" name="salario" step="0.01" min="0" value="<?= htmlspecialchars($adm['salario'] ?? '') ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Fecha de Contratación <span class="req">*</span></label>
                        <input type="date" name="fecha_contratacion" value="<?= htmlspecialchars($adm['fecha_contratacion']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Estado Laboral <span class="req">*</span></label>
                        <select name="estado_laboral" required>
                            <option value="activo" <?= strtolower($adm['estado_laboral'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="licencia" <?= strtolower($adm['estado_laboral'] ?? '') === 'licencia' ? 'selected' : '' ?>>Licencia</option>
                            <option value="inactivo" <?= strtolower($adm['estado_laboral'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo / Retirado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Observaciones -->
            <div class="section-subtitle">📝 3. Observaciones / Notas Adicionales</div>
            <div class="form-group">
                <textarea name="observaciones" rows="3"><?= htmlspecialchars($adm['observaciones'] ?? '') ?></textarea>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">← Cancelar</a>
                <div style="display:flex; gap: 0.75rem;">
                    <a href="ver.php?id=<?= $adm['id_persona'] ?>" class="btn" style="background:rgba(255,255,255,0.05);border:1px solid var(--card-border);color:var(--text-muted);">Ver Ficha</a>
                    <button type="submit" class="btn btn-primary">
                        <span>💾 Guardar Cambios</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

</body>
</html>