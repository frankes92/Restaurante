-- =====================================================================
-- YAPEZ POS v10 - Modulo de WhatsApp
-- Plantillas + Envios + Numeros de cliente + Permisos
-- =====================================================================
USE `yapez_db`;

-- ---------------------------------------------------------------------
-- 1) PLANTILLAS DE MENSAJE
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `whatsapp_plantilla` (
    `idplantilla`  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `codigo`       VARCHAR(40) NOT NULL,
    `nombre`       VARCHAR(80) NOT NULL,
    `mensaje`      TEXT NOT NULL,
    `auto_cobro`   TINYINT(1) NOT NULL DEFAULT 0
                   COMMENT '1=plantilla por defecto al cobrar (boleta o factura)',
    `tipo`         ENUM('cobro','cumple','festivo','promocion','generico')
                   NOT NULL DEFAULT 'generico',
    `activo`       TINYINT(1) NOT NULL DEFAULT 1,
    `creada`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idplantilla`),
    UNIQUE KEY `uk_wp_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) HISTORIAL DE ENVIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `whatsapp_envio` (
    `idenvio`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `idcliente`      INT(10) UNSIGNED NULL,
    `idclifact`      INT(10) UNSIGNED NULL,
    `idcomprobante`  INT(10) UNSIGNED NULL,
    `idplantilla`    INT(10) UNSIGNED NULL,
    `numero`         VARCHAR(20) NOT NULL,
    `nombre_cliente` VARCHAR(150) NULL,
    `documento`      VARCHAR(20) NULL,
    `mensaje`        TEXT NOT NULL,
    `enviado`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `idusuario`      INT(10) UNSIGNED NULL,
    `tipo`           ENUM('cobro','masivo','manual') NOT NULL DEFAULT 'manual',
    PRIMARY KEY (`idenvio`),
    KEY `idx_we_numero`   (`numero`),
    KEY `idx_we_fecha`    (`enviado`),
    KEY `idx_we_cliente`  (`idcliente`),
    KEY `idx_we_comp`     (`idcomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) Agregar campos whatsapp + cumpleanos a cliente y cliente_facturacion
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_yapez_v10_cliente`;
DELIMITER $$
CREATE PROCEDURE `sp_yapez_v10_cliente`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cliente' AND COLUMN_NAME='whatsapp'
    ) THEN
        ALTER TABLE `cliente`
            ADD COLUMN `whatsapp` VARCHAR(20) NULL AFTER `telefono`,
            ADD COLUMN `fecha_nacimiento` DATE NULL AFTER `whatsapp`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cliente_facturacion' AND COLUMN_NAME='whatsapp'
    ) THEN
        ALTER TABLE `cliente_facturacion`
            ADD COLUMN `whatsapp` VARCHAR(20) NULL AFTER `telefono`;
    END IF;
END$$
DELIMITER ;
CALL `sp_yapez_v10_cliente`();
DROP PROCEDURE IF EXISTS `sp_yapez_v10_cliente`;

-- ---------------------------------------------------------------------
-- 4) PLANTILLAS PRE-CONFIGURADAS
--    Variables disponibles: {nombre} {documento} {tipo_doc} {comprobante}
--    {tipo_comp} {total} {fecha} {empresa} {ruc_empresa} {telefono_empresa}
--    {link_pdf}
-- ---------------------------------------------------------------------
INSERT INTO `whatsapp_plantilla` (`codigo`,`nombre`,`tipo`,`auto_cobro`,`mensaje`) VALUES
('boleta', 'Boleta emitida', 'cobro', 1,
'Hola {nombre} 👋

Te enviamos tu *Boleta Electrónica*
N° {comprobante}
Total: *{total}*
{tipo_doc}: {documento}

Gracias por tu compra en *{empresa}* 🍤

Descarga tu comprobante:
{link_pdf}'),

('factura', 'Factura emitida', 'cobro', 1,
'Hola {nombre} 👋

Te enviamos tu *Factura Electrónica*
N° {comprobante}
Total: *{total}*
RUC: {documento}

Gracias por confiar en *{empresa}* 🤝

Descarga tu comprobante:
{link_pdf}'),

('cumple', 'Cumpleaños del cliente', 'cumple', 0,
'🎉 ¡Feliz cumpleaños {nombre}! 🎂

En *{empresa}* queremos celebrar contigo.
Te invitamos a disfrutar de un *15% de descuento* en tu próximo pedido como regalo de cumpleaños.

Válido durante todo el día.
¡Esperamos verte pronto! 🍤

📞 {telefono_empresa}'),

('navidad', 'Navidad', 'festivo', 0,
'🎄 ¡Feliz Navidad {nombre}! ✨

En *{empresa}* te deseamos paz, amor y mucha alegría junto a tu familia en estas fiestas.

Gracias por ser parte de nuestra familia este año 💛

Que el 2027 te traiga muchas bendiciones.

— El equipo de {empresa}'),

('ano_nuevo', 'Año Nuevo', 'festivo', 0,
'🎊 ¡Feliz Año Nuevo {nombre}! 🥳

Que este nuevo año te traiga salud, prosperidad y muchos momentos felices.

Gracias por confiar en *{empresa}* durante el año que termina.

¡Te esperamos en el 2027 con nuevos sabores y promociones! 🍤

— Tu cevichería de confianza'),

('madres', 'Día de la Madre', 'festivo', 0,
'💐 ¡Feliz Día de la Madre {nombre}! 💗

Hoy celebramos a la persona más importante.
En *{empresa}* tenemos un *menú especial* preparado con mucho cariño.

Reserva tu mesa: 📞 {telefono_empresa}

¡Te esperamos para celebrar a las mamás como se merecen!'),

('padres', 'Día del Padre', 'festivo', 0,
'👨‍👧 ¡Feliz Día del Padre {nombre}! 🍻

Celebra a papá con un buen ceviche en *{empresa}*.
Tenemos *promociones especiales* para esta fecha.

Reserva: 📞 {telefono_empresa}

¡Lo esperamos para hacer de su día algo inolvidable!'),

('patrias', 'Fiestas Patrias', 'festivo', 0,
'🇵🇪 ¡Felices Fiestas Patrias {nombre}! 🎉

Celebra el cumpleaños del Perú con la mejor comida criolla en *{empresa}*.

🍤 Combos especiales por Fiestas Patrias
🎵 Ambiente peruano

📞 {telefono_empresa}

¡Viva el Perú!'),

('promocion', 'Promoción genérica', 'promocion', 0,
'🔥 ¡Hola {nombre}! 🔥

Tenemos una *promoción especial* solo para nuestros clientes:

[Aquí describe tu promoción]

📞 {telefono_empresa}
📍 Visítanos en *{empresa}*

¡No te lo pierdas! ⏰'),

('gracias', 'Gracias por tu visita', 'generico', 0,
'¡Gracias por visitarnos {nombre}! 💛

En *{empresa}* siempre es un gusto atenderte.

Tu opinión es muy importante para nosotros.
¿Cómo estuvo tu experiencia hoy?

¡Te esperamos pronto! 🍤')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- ---------------------------------------------------------------------
-- 5) PERMISOS NUEVOS
-- ---------------------------------------------------------------------
INSERT INTO `permiso` (`codigo`,`nombre`,`descripcion`,`grupo`,`orden`) VALUES
    ('whatsapp_enviar',     'Enviar WhatsApp',         'Enviar mensajes individuales (al cobrar)', 'whatsapp', 50),
    ('whatsapp_plantillas', 'Plantillas WhatsApp',     'Gestionar plantillas de mensajes',         'whatsapp', 51),
    ('whatsapp_masivo',     'Envío masivo WhatsApp',   'Campañas a múltiples clientes',            'whatsapp', 52)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Admin: todos
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='admin'), p.idpermiso
FROM permiso p WHERE p.grupo='whatsapp';

-- Cajero: solo enviar individual
INSERT IGNORE INTO `rol_permiso` (`idrol`,`idpermiso`)
SELECT (SELECT idrol FROM rol WHERE codigo='cajero'), p.idpermiso
FROM permiso p WHERE p.codigo='whatsapp_enviar';
