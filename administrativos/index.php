<?php
require_once '../conexion.php';

// Consulta con LEFT JOIN a personas para mostrar nombres, apellidos y CI
$sql = "SELECT a.id_persona, 
               a.codigo_administrativo, 
               a.cargo, 
               a.fecha_contratacion, 
               a.estado_laboral,
               p.documento_identidad, 
               p.nombres, 
               p.apellidos, 
               p.telefono, 
               p.correo
        FROM administrativos a
        LEFT JOIN personas p ON a.id_persona = p.id_persona
        ORDER BY a.id_persona DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute();
$administrativos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mensajes de alerta según parámetro GET
$mensaje = '';
$tipoMensaje = 'success';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'creado') {
        $mensaje = '✅ Personal administrativo registrado exitosamente.';
    } elseif ($_GET['msg'] === 'actualizado') {
        $mensaje = '✅ Datos del administrativo actualizados con éxito.';
    } elseif ($_GET['msg'] === 'eliminado') {
        $mensaje = '🗑️ Registro administrativo eliminado correctamente.';
    } elseif ($_GET['msg'] === 'error') {
        $mensaje = '❌ Ocurrió un error al procesar la solicitud.';
        $tipoMensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Administrativo | Colegio Pedagógico</title>
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
            --amber: #f59e0b;
            --rose: #ef4444;
            --chip-bg: rgba(255, 255, 255, 0.04);
            --table-hover: rgba(255, 255, 255, 0.03);
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
            position: relative;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* Ambient glow */
        body::before {
            content: '';
            position: fixed;
            top: -15%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        /* Header Navigation */
        .header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-badge {
            width: 48px;
            height: 48px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .header-title h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
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

        /* Alert message */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* Search & Filter Bar */
        .toolbar {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 0.5rem 0.85rem;
            width: 100%;
            max-width: 360px;
        }

        .search-box input {
            background: transparent;
            border: none;
            color: #ffffff;
            font-size: 0.875rem;
            outline: none;
            width: 100%;
        }

        .search-box input::placeholder {
            color: #6b7280;
        }

        .stats-counter {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .stats-counter strong {
            color: #ffffff;
        }

        /* Card & Table */
        .table-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        thead {
            background: rgba(15, 23, 42, 0.7);
            border-bottom: 1px solid var(--card-border);
        }

        th {
            padding: 1rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        td {
            padding: 1.1rem 1.25rem;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #d1d5db;
        }

        tbody tr {
            transition: background 0.15s ease;
        }

        tbody tr:hover {
            background: var(--table-hover);
        }

        .col-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 600;
            color: #a5b4fc;
        }

        .col-name {
            font-weight: 600;
            color: #ffffff;
        }

        .col-ci {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .badge-cargo {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f3f4f6;
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
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

        /* Actions */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-action.ver {
            background: rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
        }
        .btn-action.ver:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-action.editar {
            background: rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        .btn-action.editar:hover {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-action.eliminar {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .btn-action.eliminar:hover {
            background: var(--rose);
            color: #ffffff;
        }

        .empty-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state span {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Nav -->
    <header class="header-nav">
        <div class="header-title">
            <div class="icon-badge">💼</div>
            <div>
                <h1>Gestión de Personal Administrativo</h1>
                <p>Colegio Pedagógico &bull; Módulo de Recursos Humanos</p>
            </div>
        </div>

        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">
                <span>&larr; Menú Principal</span>
            </a>
            <a href="crear.php" class="btn btn-primary">
                <span>+ Nuevo Administrativo</span>
            </a>
        </div>
    </header>

    <!-- Feedback alert -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMensaje ?>">
            <span><?= htmlspecialchars($mensaje) ?></span>
            <button onclick="this.parentElement.remove();" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-box">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #6b7280;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input type="text" id="filtro-tabla" placeholder="Buscar por nombre, código o cargo..." onkeyup="filtrarTabla()">
        </div>

        <div class="stats-counter">
            Total registrados: <strong><?= count($administrativos) ?></strong>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <?php if (!empty($administrativos)): ?>
            <table id="tabla-administrativos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre y Apellidos</th>
                        <th>Documento (CI)</th>
                        <th>Cargo Institucional</th>
                        <th>Fecha Contratación</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($administrativos as $adm): ?>
                        <tr>
                            <td class="col-code">
                                <?= htmlspecialchars($adm['codigo_administrativo']) ?>
                            </td>

                            <td>
                                <div class="col-name">
                                    <?php if (!empty($adm['nombres']) || !empty($adm['apellidos'])): ?>
                                        <?= htmlspecialchars(trim(($adm['nombres'] ?? '') . ' ' . ($adm['apellidos'] ?? ''))) ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">ID Persona: <?= htmlspecialchars($adm['id_persona']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($adm['correo'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($adm['correo']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td class="col-ci">
                                <?= htmlspecialchars($adm['documento_identidad'] ?? 'S/D') ?>
                            </td>

                            <td>
                                <span class="badge-cargo">
                                    <?= htmlspecialchars($adm['cargo']) ?>
                                </span>
                            </td>

                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">
                                <?= htmlspecialchars($adm['fecha_contratacion']) ?>
                            </td>

                            <td>
                                <?php
                                    $estado = strtolower($adm['estado_laboral']);
                                    $claseEstado = 'status-inactivo';
                                    if ($estado === 'activo') $claseEstado = 'status-activo';
                                    elseif ($estado === 'licencia') $claseEstado = 'status-licencia';
                                ?>
                                <span class="status-badge <?= $claseEstado ?>">
                                    <?= htmlspecialchars(ucfirst($adm['estado_laboral'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions-cell" style="justify-content: flex-end;">
                                    <a class="btn-action ver" href="ver.php?id=<?= $adm['id_persona'] ?>" title="Ver Ficha">
                                        Ver
                                    </a>

                                    <a class="btn-action editar" href="editar.php?id=<?= $adm['id_persona'] ?>" title="Editar">
                                        Editar
                                    </a>

                                    <a class="btn-action eliminar" 
                                       href="eliminar.php?id=<?= $adm['id_persona'] ?>" 
                                       onclick="return confirm('¿Confirma que desea eliminar al administrativo <?= htmlspecialchars(addslashes($adm['codigo_administrativo'])) ?>?');" 
                                       title="Eliminar">
                                        Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <span>📁</span>
                <h3>No hay administrativos registrados</h3>
                <p>Haz clic en "Nuevo Administrativo" para agregar el primero.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filtrarTabla() {
    const input = document.getElementById('filtro-tabla');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('tabla-administrativos');
    if (!table) return;
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const text = tr[i].textContent.toLowerCase();
        tr[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
    }
}
</script>

</body>
</html>