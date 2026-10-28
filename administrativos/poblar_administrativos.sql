-- ============================================================
-- SCRIPT DE POBLADO ("POBLIS"): DATOS PARA ADMINISTRATIVOS
-- Base de Datos: colegio_pedagogico
-- ============================================================

USE colegio_pedagogico;

-- 1. Insertar Personas correspondientes al personal administrativo
INSERT INTO personas (id_persona, documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, estado)
VALUES
(1, '4892019-LP', 'Roberto Carlos', 'Mendoza Quisbert', '1978-05-14', 'M', '71524310', 'director@colegiopedagogico.edu.bo', 'Av. América #1420', 'ACTIVO'),
(2, '5631892-CB', 'Carmen Gloria', 'Arce Torrico', '1984-11-20', 'F', '72098431', 'secretaria@colegiopedagogico.edu.bo', 'Calle Colombia #450', 'ACTIVO'),
(3, '6102938-SC', 'Marco Antonio', 'Paredes Vaca', '1982-08-09', 'M', '76541290', 'contabilidad@colegiopedagogico.edu.bo', 'Av. San Martín #780', 'ACTIVO'),
(4, '7891234-OR', 'Fernando Javier', 'Vargas Rocha', '1980-03-25', 'M', '79812405', 'regencia@colegiopedagogico.edu.bo', 'Calle Potosí #210', 'ACTIVO'),
(5, '8472910-LP', 'Laura Beatriz', 'Quispe Mamani', '1990-12-03', 'F', '73019845', 'sistemas@colegiopedagogico.edu.bo', 'Zona Miraflores #65', 'ACTIVO'),
(6, '9123847-TJ', 'Sofía Elena', 'Valenzuela Ríos', '1988-07-18', 'F', '74128930', 'biblioteca@colegiopedagogico.edu.bo', 'Av. Busch #312', 'ACTIVO'),
(9938171, '9938171-LP', 'Marcelo', 'Sánchez Quiroga', '1975-01-01', 'M', '70000001', 'direccion@colegio.edu.bo', 'Sede Central', 'ACTIVO')
ON DUPLICATE KEY UPDATE 
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    telefono = VALUES(telefono),
    correo = VALUES(correo);

-- 2. Insertar Datos de Administrativos
INSERT INTO administrativos (id_persona, codigo_administrativo, cargo, fecha_contratacion, estado_laboral)
VALUES
(1, 'ADM-2024-001', 'Director General', '2024-01-15', 'activo'),
(2, 'ADM-2024-002', 'Secretaria Académica', '2024-02-01', 'activo'),
(3, 'ADM-2025-003', 'Contador General', '2025-01-10', 'activo'),
(4, 'ADM-2025-004', 'Regente Escolar', '2025-02-15', 'activo'),
(5, 'ADM-2026-005', 'Administrador de Sistemas / TIC', '2026-01-10', 'activo'),
(6, 'ADM-2026-006', 'Encargada de Biblioteca', '2026-02-01', 'licencia'),
(9938171, 'so262-1213', 'Director de Turno', '2026-10-01', 'activo')
ON DUPLICATE KEY UPDATE 
    cargo = VALUES(cargo),
    fecha_contratacion = VALUES(fecha_contratacion),
    estado_laboral = VALUES(estado_laboral);
