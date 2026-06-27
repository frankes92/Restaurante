-- Migración v11: Habilitar metodo_pago = 'mixto'
-- Permite combinar Efectivo + Yape/Plin en una sola orden.
-- El desglose se guarda en pago_metadata (JSON) como { efectivo: X, yape: Y }.

ALTER TABLE `orden`
  MODIFY `metodo_pago` ENUM('','efectivo','tarjeta','yape','transferencia','mixto')
  NOT NULL DEFAULT '';
