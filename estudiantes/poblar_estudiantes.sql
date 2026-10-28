-- ============================================================
-- SCRIPT DE POBLADO ("POBLIS"): ESTUDIANTES Y FAMILIAS
-- Separación Primaria / Secundaria, Paralelos (Rojo, Azul, Blanco)
-- y Soporte de Hermanos (2 o 3 hijos por apoderado)
-- ============================================================

USE colegio_pedagogico;

-- 1. Gestión Activa (2026)
INSERT INTO gestiones (id_gestion, anio, fecha_inicio, fecha_fin, estado)
VALUES (1, 2026, '2026-02-02', '2026-11-30', 'ACTIVA')
ON DUPLICATE KEY UPDATE anio = VALUES(anio), estado = VALUES(estado);

-- 2. Todas las Ofertas Académicas para Primaria y Secundaria en Rojo, Azul y Blanco
INSERT IGNORE INTO ofertas_academicas (id_gestion, id_grado, id_paralelo, capacidad_maxima, aula, estado)
SELECT 1, g.id_grado, p.id_paralelo, 35, CONCAT('Aula ', g.orden, ' - ', p.nombre), 'ACTIVA'
FROM grados g
CROSS JOIN paralelos p
ORDER BY g.id_grado, p.id_paralelo;

-- 3. Estudiantes y Familias
-- FAMILIA 1: Flores Choque (3 hermanos: Alejandro en 1ro Primaria, Mateo en 3ro Primaria, Luciana en 5to Secundaria)
-- Tutor común: Gabriel Flores Mamani (Padre) y Elena Choque (Madre)
INSERT INTO personas (id_persona, documento_identidad, nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, estado)
VALUES
(101, '12457890-LP', 'Alejandro Gabriel', 'Flores Choque', '2019-05-14', 'M', '71000101', 'aflores@colegiopedagogico.edu.bo', 'Zona Sopocachi #450', 'ACTIVO'),
(102, '13579246-CB', 'Valentina Lucía', 'Ríos Mamani', '2019-08-20', 'F', '72000102', 'vrios@colegiopedagogico.edu.bo', 'Av. Heroínas #890', 'ACTIVO'),
(103, '14680135-SC', 'Mateo Sebastián', 'Vaca Díez Guzmán', '2018-03-11', 'M', '73000103', 'mvaca@colegiopedagogico.edu.bo', 'Barrio Equipetrol #12', 'ACTIVO'),
(104, '15791246-OR', 'Camila Nicole', 'Morales Benavides', '2014-11-25', 'F', '74000104', 'cmorales@colegiopedagogico.edu.bo', 'Calle 6 de Octubre #320', 'ACTIVO'),
(105, '16802357-PT', 'Lucas Benjamín', 'Quisbert Arancibia', '2013-09-08', 'M', '75000105', 'lquisbert@colegiopedagogico.edu.bo', 'Av. Murillo #670', 'ACTIVO'),
(106, '17913468-TJ', 'Luciana Belén', 'Torres Valverde', '2011-07-19', 'F', '76000106', 'ltorres@colegiopedagogico.edu.bo', 'Calle Sucre #210', 'ACTIVO'),
(107, '18024579-CH', 'Joaquín Ignacio', 'Mercado Zeballos', '2009-01-30', 'M', '77000107', 'jmercado@colegiopedagogico.edu.bo', 'Av. Hernando Siles #145', 'ACTIVO'),
(108, '19135680-BN', 'Mariana Isabel', 'Paz Justiniano', '2008-04-16', 'F', '78000108', 'mpaz@colegiopedagogico.edu.bo', 'Calle Beni #55', 'ACTIVO'),
(109, '20246791-PD', 'Santiago Daniel', 'Alvarado Roca', '2008-10-02', 'M', '79000109', 'salvarado@colegiopedagogico.edu.bo', 'Av. 9 de Febrero #400', 'ACTIVO'),
(110, '21357802-LP', 'Isabella Sofía', 'Condori Quispe', '2014-06-12', 'F', '70000110', 'icondori@colegiopedagogico.edu.bo', 'Zona Miraflores #810', 'ACTIVO'),
(111, '22457891-LP', 'Mateo Gabriel', 'Flores Choque', '2017-08-10', 'M', '71000111', 'mflores@colegiopedagogico.edu.bo', 'Zona Sopocachi #450', 'ACTIVO'),
(112, '23568902-LP', 'Luciana Sofía', 'Flores Choque', '2010-02-14', 'F', '71000112', 'lflores@colegiopedagogico.edu.bo', 'Zona Sopocachi #450', 'ACTIVO'),
(113, '24679013-CB', 'Leonardo Andrés', 'Ríos Mamani', '2013-11-04', 'M', '72000113', 'lrios@colegiopedagogico.edu.bo', 'Av. Heroínas #890', 'ACTIVO')
ON DUPLICATE KEY UPDATE 
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    documento_identidad = VALUES(documento_identidad),
    fecha_nacimiento = VALUES(fecha_nacimiento),
    sexo = VALUES(sexo),
    telefono = VALUES(telefono);

-- 4. Registro Estudiantil
INSERT INTO estudiantes (id_persona, codigo_estudiante, fecha_ingreso, observaciones)
VALUES
(101, 'EST-2026-001', '2026-02-02', '1er hermano registrado (Familia Flores Choque). 1ro Primaria.'),
(102, 'EST-2026-002', '2026-02-02', 'Hermana menor (Familia Ríos Mamani). 1ro Primaria.'),
(103, 'EST-2025-054', '2025-02-03', 'Promovido a 2do de Primaria.'),
(104, 'EST-2021-089', '2021-02-01', '6to de Primaria. Destacada en ciencias.'),
(105, 'EST-2026-003', '2026-02-02', '1ro de Secundaria. Traslado homologado.'),
(106, 'EST-2020-045', '2020-02-03', '3ro de Secundaria. Club de debate.'),
(107, 'EST-2018-012', '2018-02-05', '5to de Secundaria.'),
(108, 'EST-2017-008', '2017-02-06', '6to de Secundaria. Postulante a excelencia.'),
(109, 'EST-2017-009', '2017-02-06', '6to de Secundaria.'),
(110, 'EST-2026-004', '2026-02-15', '6to de Primaria. Traslado interdepartamental.'),
(111, 'EST-2026-005', '2026-02-02', '2do hermano nuevo registrado (Familia Flores Choque). 3ro Primaria.'),
(112, 'EST-2026-006', '2026-02-02', '3er hermano nuevo registrado (Familia Flores Choque). 5to Secundaria.'),
(113, 'EST-2026-007', '2026-02-02', 'Hermano mayor nuevo (Familia Ríos Mamani). 1ro Secundaria.')
ON DUPLICATE KEY UPDATE 
    codigo_estudiante = VALUES(codigo_estudiante),
    fecha_ingreso = VALUES(fecha_ingreso),
    observaciones = VALUES(observaciones);

-- 5. Contactos y Tutores (Vínculo Familiar entre Hermanos)
-- Gabriel Flores Mamani (Tel: 71829304) es tutor de 101, 111 y 112 (3 hermanos).
-- Lucía Mamani de Ríos (Tel: 72930415) es tutora de 102 y 113 (2 hermanos).
INSERT INTO contactos_estudiantes (id_contacto, id_estudiante, nombre_completo, parentesco, telefono, correo, es_contacto_emergencia, es_representante, observaciones)
VALUES
(1, 101, 'Gabriel Flores Mamani', 'Padre', '71829304', 'gflores@gmail.com', 1, 1, 'Tutor legal titular de Alejandro, Mateo y Luciana.'),
(2, 102, 'Lucía Mamani de Ríos', 'Madre', '72930415', 'lrios@hotmail.com', 1, 1, 'Tutora titular de Valentina y Leonardo.'),
(3, 103, 'Sebastián Vaca Díez', 'Padre', '73041526', 'svacadiez@gmail.com', 1, 1, 'Contacto directo por WhatsApp.'),
(4, 104, 'Nicole Benavides Rojas', 'Madre', '74152637', 'nbenavides@yahoo.es', 1, 1, 'Apoderada titular.'),
(5, 105, 'Benjamín Quisbert Ramos', 'Padre', '75263748', 'bquisbert@gmail.com', 1, 1, 'Padre apoderado.'),
(6, 106, 'Bélgica Valverde Ortiz', 'Madre', '76374859', 'bvalverde@gmail.com', 1, 1, 'Tutor académico.'),
(7, 107, 'Ignacio Mercado Roca', 'Padre', '77485960', 'imercado@cotas.com.bo', 1, 1, 'Contacto y apoderado.'),
(8, 108, 'María Justiniano de Paz', 'Madre', '78596071', 'mjustiniano@gmail.com', 1, 1, 'Representante titular.'),
(9, 109, 'Daniel Alvarado Siles', 'Padre', '79607182', 'dalvarado@gmail.com', 1, 1, 'Tutor legal.'),
(10, 110, 'Sonia Quispe de Condori', 'Madre', '70718293', 'squispe@gmail.com', 1, 1, 'Madre de familia y tutora.'),
(11, 111, 'Gabriel Flores Mamani', 'Padre', '71829304', 'gflores@gmail.com', 1, 1, 'Mismo tutor que Alejandro (101) y Luciana (112).'),
(12, 112, 'Gabriel Flores Mamani', 'Padre', '71829304', 'gflores@gmail.com', 1, 1, 'Mismo tutor que Alejandro (101) y Mateo (111).'),
(13, 113, 'Lucía Mamani de Ríos', 'Madre', '72930415', 'lrios@hotmail.com', 1, 1, 'Misma tutora que Valentina (102).'),
(14, 101, 'Elena Choque de Flores', 'Madre', '71829305', 'echoque@gmail.com', 1, 0, 'Madre de Alejandro, Mateo y Luciana.'),
(15, 111, 'Elena Choque de Flores', 'Madre', '71829305', 'echoque@gmail.com', 1, 0, 'Madre de Alejandro, Mateo y Luciana.'),
(16, 112, 'Elena Choque de Flores', 'Madre', '71829305', 'echoque@gmail.com', 1, 0, 'Madre de Alejandro, Mateo y Luciana.'),
(17, 102, 'Marcelo Ríos Vargas', 'Padre', '72930416', 'mrios@hotmail.com', 1, 0, 'Padre de Valentina y Leonardo.'),
(18, 113, 'Marcelo Ríos Vargas', 'Padre', '72930416', 'mrios@hotmail.com', 1, 0, 'Padre de Valentina y Leonardo.')
ON DUPLICATE KEY UPDATE nombre_completo = VALUES(nombre_completo), telefono = VALUES(telefono);

-- 6. Matrículas en Ofertas Académicas Específicas
-- 101: 1ro Primaria Rojo
-- 102: 1ro Primaria Azul
-- 103: 2do Primaria Blanco
-- 104: 6to Primaria Rojo
-- 105: 1ro Secundaria Rojo
-- 106: 3ro Secundaria Azul
-- 107: 5to Secundaria Rojo
-- 108: 6to Secundaria Rojo
-- 109: 6to Secundaria Azul
-- 110: 6to Primaria Blanco
-- 111: 3ro Primaria Azul (Hermano de 101)
-- 112: 5to Secundaria Blanco (Hermana de 101 y 111)
-- 113: 1ro Secundaria Blanco (Hermano de 102)

DELETE FROM matriculas WHERE id_estudiante >= 101 AND id_estudiante <= 113;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 1, 101, o.id_oferta, '2026-02-02', 'NUEVO', 'ACTIVA', '1ro de Primaria Rojo'
FROM ofertas_academicas o WHERE o.id_grado = 2 AND o.id_paralelo = 1 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 2, 102, o.id_oferta, '2026-02-02', 'NUEVO', 'ACTIVA', '1ro de Primaria Azul'
FROM ofertas_academicas o WHERE o.id_grado = 2 AND o.id_paralelo = 2 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 3, 103, o.id_oferta, '2026-02-03', 'REGULAR', 'ACTIVA', '2do de Primaria Blanco'
FROM ofertas_academicas o WHERE o.id_grado = 3 AND o.id_paralelo = 3 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 4, 104, o.id_oferta, '2026-02-01', 'REGULAR', 'ACTIVA', '6to de Primaria Rojo'
FROM ofertas_academicas o WHERE o.id_grado = 7 AND o.id_paralelo = 1 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 5, 105, o.id_oferta, '2026-02-02', 'TRASLADO', 'ACTIVA', '1ro de Secundaria Rojo'
FROM ofertas_academicas o WHERE o.id_grado = 8 AND o.id_paralelo = 1 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 6, 106, o.id_oferta, '2026-02-03', 'REGULAR', 'ACTIVA', '3ro de Secundaria Azul'
FROM ofertas_academicas o WHERE o.id_grado = 10 AND o.id_paralelo = 2 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 7, 107, o.id_oferta, '2026-02-05', 'REGULAR', 'ACTIVA', '5to de Secundaria Rojo'
FROM ofertas_academicas o WHERE o.id_grado = 12 AND o.id_paralelo = 1 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 8, 108, o.id_oferta, '2026-02-06', 'REGULAR', 'ACTIVA', '6to de Secundaria Rojo'
FROM ofertas_academicas o WHERE o.id_grado = 13 AND o.id_paralelo = 1 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 9, 109, o.id_oferta, '2026-02-06', 'REGULAR', 'ACTIVA', '6to de Secundaria Azul'
FROM ofertas_academicas o WHERE o.id_grado = 13 AND o.id_paralelo = 2 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 10, 110, o.id_oferta, '2026-02-15', 'TRASLADO', 'ACTIVA', '6to de Primaria Blanco'
FROM ofertas_academicas o WHERE o.id_grado = 7 AND o.id_paralelo = 3 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 11, 111, o.id_oferta, '2026-02-02', 'REGULAR', 'ACTIVA', '3ro de Primaria Azul (Hermano de 101 y 112)'
FROM ofertas_academicas o WHERE o.id_grado = 4 AND o.id_paralelo = 2 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 12, 112, o.id_oferta, '2026-02-02', 'REGULAR', 'ACTIVA', '5to de Secundaria Blanco (Hermana de 101 y 111)'
FROM ofertas_academicas o WHERE o.id_grado = 12 AND o.id_paralelo = 3 LIMIT 1;

INSERT INTO matriculas (id_matricula, id_estudiante, id_oferta, fecha_matricula, tipo_ingreso, estado, observaciones)
SELECT 13, 113, o.id_oferta, '2026-02-02', 'NUEVO', 'ACTIVA', '1ro de Secundaria Blanco (Hermano de 102)'
FROM ofertas_academicas o WHERE o.id_grado = 8 AND o.id_paralelo = 3 LIMIT 1;
