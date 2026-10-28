<?php
/**
 * Verificador de Conexión a la Base de Datos
 * Sistema de Gestión Escolar - Colegio Pedagógico
 */

define('PROBAR_CONEXION', true);

$estadoConexion = false;
$mensajeError   = null;
$errorTrace     = null;
$codigoError    = null;
$sugerencias    = [];
$infoServidor   = [];
$tablas         = [];
$tiempoRespuesta = 0;

$inicioTiempo = microtime(true);

try {
    require_once __DIR__ . '/conexion.php';

    if (!isset($conexion) || !($conexion instanceof PDO)) {
        throw new Exception("El archivo conexion.php no proporcionó una instancia válida de PDO en \$conexion.");
    }

    // Probar consulta y medir latencia
    $consultaDb = $conexion->query("SELECT DATABASE() AS base_datos");
    $resDb = $consultaDb->fetch();
    $nombreBaseDatos = $resDb['base_datos'] ?? 'Desconocida';

    // Obtener versión y charset
    $versionQuery = $conexion->query("SELECT VERSION() AS version, @@character_set_connection AS charset, @@collation_connection AS collation");
    $resVersion = $versionQuery->fetch();

    // Obtener tablas de la base de datos
    $tablasQuery = $conexion->query("SHOW TABLES");
    $tablas = $tablasQuery->fetchAll(PDO::FETCH_COLUMN);

    $finTiempo = microtime(true);
    $tiempoRespuesta = round(($finTiempo - $inicioTiempo) * 1000, 2);

    $estadoConexion = true;
    $infoServidor = [
        'base_datos'        => $nombreBaseDatos,
        'host'              => $host ?? 'localhost',
        'usuario'           => $usuario ?? 'root',
        'version'           => $resVersion['version'] ?? 'N/A',
        'charset'           => $resVersion['charset'] ?? 'utf8mb4',
        'collation'         => $resVersion['collation'] ?? 'utf8mb4_unicode_ci',
        'total_tablas'      => count($tablas),
        'tiempo_respuesta'  => $tiempoRespuesta . ' ms',
        'driver'            => $conexion->getAttribute(PDO::ATTR_DRIVER_NAME),
    ];

} catch (PDOException $e) {
    $estadoConexion = false;
    $mensajeError = $e->getMessage();
    $codigoError = $e->getCode();

    // Diagnósticos inteligentes comunes
    if (strpos($mensajeError, '2002') !== false || strpos($mensajeError, 'refused') !== false) {
        $sugerencias[] = "El servicio de MySQL no está corriendo. Abre el <strong>Panel de Control de XAMPP</strong> y haz clic en <strong>Start</strong> en el módulo <strong>MySQL</strong>.";
    } elseif (strpos($mensajeError, '1049') !== false || strpos($mensajeError, 'Unknown database') !== false) {
        $sugerencias[] = "La base de datos especificada no existe en MySQL. Ingresa a <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a> y asegúrate de haber creado la base de datos o haber importado el script SQL.";
    } elseif (strpos($mensajeError, '1045') !== false || strpos($mensajeError, 'Access denied') !== false) {
        $sugerencias[] = "Acceso denegado: El usuario o contraseña en <code>conexion.php</code> son incorrectos. En XAMPP por defecto el usuario es <code>root</code> y la contraseña está vacía <code>\"\"</code>.";
    } else {
        $sugerencias[] = "Verifica los parámetros de configuración en el archivo <code>conexion.php</code>.";
    }
} catch (Exception $e) {
    $estadoConexion = false;
    $mensajeError = $e->getMessage();
    $sugerencias[] = "Revisa la configuración del archivo <code>conexion.php</code>.";
}

// Soporte para ejecución en consola (CLI)
if (php_sapi_name() === 'cli') {
    echo "\n=======================================================\n";
    echo "       DIAGNÓSTICO DE CONEXIÓN A BASE DE DATOS\n";
    echo "=======================================================\n";
    if ($estadoConexion) {
        echo "[✓] ESTADO: CONEXIÓN EXITOSA\n";
        echo " - Base de datos : " . $infoServidor['base_datos'] . "\n";
        echo " - Host          : " . $infoServidor['host'] . "\n";
        echo " - Usuario       : " . $infoServidor['usuario'] . "\n";
        echo " - Servidor BD   : " . $infoServidor['version'] . "\n";
        echo " - Tablas (" . count($tablas) . ")   : " . implode(', ', array_slice($tablas, 0, 8)) . (count($tablas) > 8 ? " ... (y " . (count($tablas) - 8) . " más)" : "") . "\n";
        echo " - Latencia      : " . $infoServidor['tiempo_respuesta'] . "\n";
    } else {
        echo "[✗] ESTADO: ERROR DE CONEXIÓN\n";
        echo " - Mensaje: " . $mensajeError . "\n";
        if (!empty($sugerencias)) {
            echo " - Sugerencias:\n";
            foreach ($sugerencias as $s) {
                echo "   * " . strip_tags($s) . "\n";
            }
        }
    }
    echo "=======================================================\n\n";
    exit($estadoConexion ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión | Colegio Pedagógico</title>
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
            --accent-green: #10b981;
            --accent-green-glow: rgba(16, 185, 129, 0.25);
            --accent-red: #ef4444;
            --accent-red-glow: rgba(239, 68, 68, 0.25);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --chip-bg: rgba(255, 255, 255, 0.04);
            --chip-border: rgba(255, 255, 255, 0.08);
            --metric-bg: rgba(15, 23, 42, 0.6);
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient background glow */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: 20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: 15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, <?= $estadoConexion ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' ?> 0%, rgba(0,0,0,0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 860px;
            position: relative;
            z-index: 1;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            animation: cardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--card-border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
        }

        .brand-text h1 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .brand-text p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.6rem 1.15rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .status-badge.success {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 20px var(--accent-green-glow);
        }

        .status-badge.error {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 20px var(--accent-red-glow);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: relative;
        }

        .status-badge.success .status-dot {
            background-color: var(--accent-green);
        }
        .status-badge.success .status-dot::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--accent-green);
            opacity: 0.6;
            animation: pulseDot 2s infinite;
        }

        .status-badge.error .status-dot {
            background-color: var(--accent-red);
        }

        @keyframes pulseDot {
            0% { transform: scale(0.8); opacity: 0.8; }
            50% { transform: scale(1.6); opacity: 0; }
            100% { transform: scale(0.8); opacity: 0; }
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .metric-card {
            background: var(--metric-bg);
            border: 1px solid var(--chip-border);
            border-radius: 16px;
            padding: 1.25rem;
            transition: all 0.2s ease;
        }

        .metric-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .metric-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.35rem;
        }

        .metric-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
        }

        /* Tables section */
        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #d1d5db;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tables-wrapper {
            background: var(--metric-bg);
            border: 1px solid var(--chip-border);
            border-radius: 16px;
            padding: 1.25rem;
            max-height: 220px;
            overflow-y: auto;
        }

        .tables-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .table-chip {
            background: var(--chip-bg);
            border: 1px solid var(--chip-border);
            color: #e5e7eb;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.15s ease;
        }

        .table-chip:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .table-chip svg {
            width: 13px;
            height: 13px;
            opacity: 0.6;
        }

        /* Error block */
        .error-box {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .error-title {
            color: #f87171;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #fca5a5;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-top: 0.75rem;
            word-break: break-all;
        }

        .suggestions {
            margin-top: 1.25rem;
            padding-left: 1.25rem;
            color: #d1d5db;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .suggestions li {
            margin-bottom: 0.5rem;
        }

        .suggestions a {
            color: #818cf8;
            text-decoration: underline;
        }

        /* Actions & Navigation */
        .actions {
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
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #d1d5db;
            border: 1px solid var(--chip-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .quick-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .quick-links a {
            color: #a5b4fc;
            text-decoration: none;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--chip-border);
            transition: all 0.2s ease;
        }

        .quick-links a:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            color: #ffffff;
        }

        /* Scrollbar styling */
        .tables-wrapper::-webkit-scrollbar {
            width: 6px;
        }
        .tables-wrapper::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        .tables-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 3px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <!-- Header -->
        <header class="header">
            <div class="brand">
                <div class="brand-icon">🏫</div>
                <div class="brand-text">
                    <h1>Colegio Pedagógico</h1>
                    <p>Verificación del Sistema de Base de Datos</p>
                </div>
            </div>

            <?php if ($estadoConexion): ?>
                <div class="status-badge success">
                    <span class="status-dot"></span>
                    <span>Conexión Exitosa</span>
                </div>
            <?php else: ?>
                <div class="status-badge error">
                    <span class="status-dot"></span>
                    <span>Error de Conexión</span>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($estadoConexion): ?>
            <!-- Metric Cards -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Base de Datos</div>
                    <div class="metric-value"><?= htmlspecialchars($infoServidor['base_datos']) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Host / Servidor</div>
                    <div class="metric-value"><?= htmlspecialchars($infoServidor['host']) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Motor MySQL</div>
                    <div class="metric-value" style="font-size: 0.95rem;"><?= htmlspecialchars($infoServidor['version']) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Latencia Ping</div>
                    <div class="metric-value" style="color: var(--accent-green);"><?= htmlspecialchars($infoServidor['tiempo_respuesta']) ?></div>
                </div>
            </div>

            <!-- Tables Section -->
            <div class="section-title">
                <span>Tablas Detectadas en la Base de Datos</span>
                <span style="font-size: 0.8rem; color: var(--accent-green); font-family: 'JetBrains Mono', monospace;">
                    <?= count($tablas) ?> tablas activas
                </span>
            </div>

            <div class="tables-wrapper">
                <div class="tables-list">
                    <?php if (!empty($tablas)): ?>
                        <?php foreach ($tablas as $tabla): ?>
                            <div class="table-chip">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <?= htmlspecialchars($tabla) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">No se encontraron tablas en la base de datos.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Error State -->
            <div class="error-box">
                <div class="error-title">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>No se pudo conectar con el servidor MySQL</span>
                </div>
                
                <div class="error-message">
                    <?= htmlspecialchars($mensajeError) ?>
                </div>

                <?php if (!empty($sugerencias)): ?>
                    <div style="margin-top: 1.25rem; font-weight: 600; color: #f3f4f6; font-size: 0.9rem;">
                        Pasos recomendados para solucionarlo:
                    </div>
                    <ul class="suggestions">
                        <?php foreach ($sugerencias as $sugerencia): ?>
                            <li><?= $sugerencia ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer Actions -->
        <footer class="actions">
            <div>
                <button onclick="window.location.reload();" class="btn btn-primary" id="btn-reintentar">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Volver a probar</span>
                </button>
            </div>

            <div class="quick-links">
                <span>Accesos rápidos:</span>
                <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>
                <a href="estudiantes/">Estudiantes</a>
                <a href="docentes/">Docentes</a>
                <a href="administrativos/">Administrativos</a>
            </div>
        </footer>
    </div>
</div>

</body>
</html>