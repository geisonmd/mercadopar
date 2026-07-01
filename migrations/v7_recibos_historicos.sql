-- Migración v7 — Recibos históricos (Enero a Mayo 2026)
-- Extraídos dos docx do Google Drive. Ajustar colaborador_id se os IDs
-- no seu banco forem diferentes de: Geison=2, Fabián=3, Rolando=4.

-- ===== GEISON THIAGO MARCON DREON (id=2) =====

-- Enero 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (2,1,2026,1100,0,0,0,0,7227000,'2026-02-02','','GS',6570,7227000,'[]');

-- Febrero 2026 (2 anticipos: 11/02 y 25/02, Gs. 2.000.000 cada uno)
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (2,2,2026,1100,0,0,4000000,0,3040000,'2026-03-03','','GS',6400,7040000,
    '[{"fecha":"2026-02-11","descripcion":"Antecipo","tipo":"debito","monto":2000000},{"fecha":"2026-02-25","descripcion":"Antecipo","tipo":"debito","monto":2000000}]');

-- Marzo 2026 (4 anticipos: 14/03, 19/03 x2, 20/03)
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (2,3,2026,1100,0,0,3000000,0,4095000,'2026-04-01','','GS',6450,7095000,
    '[{"fecha":"2026-03-14","descripcion":"Antecipo","tipo":"debito","monto":1000000},{"fecha":"2026-03-19","descripcion":"Antecipo","tipo":"debito","monto":1000000},{"fecha":"2026-03-19","descripcion":"Antecipo","tipo":"debito","monto":500000},{"fecha":"2026-03-20","descripcion":"Antecipo","tipo":"debito","monto":500000}]');

-- Abril 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (2,4,2026,1100,0,0,0,0,6490000,'2026-05-01','','GS',5900,6490000,'[]');

-- Mayo 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (2,5,2026,1100,0,0,0,0,6710000,'2026-06-01','','GS',6100,6710000,'[]');


-- ===== WALTER FABIÁN ROJAS CENTURIÓN (id=3) =====

-- Enero 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (3,1,2026,1100,0,0,0,0,7227000,'2026-02-02','','GS',6570,7227000,'[]');

-- Febrero 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (3,2,2026,1100,0,0,0,0,7040000,'2026-03-04','','GS',6400,7040000,'[]');

-- Marzo 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (3,3,2026,1100,0,0,0,0,7095000,'2026-04-01','','GS',6450,7095000,'[]');

-- Abril 2026 (pago em USD, sin conversión)
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (3,4,2026,1100,0,0,0,0,1100,'2026-05-01','','USD',0,1100,'[]');

-- Mayo 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (3,5,2026,1100,0,0,0,0,6710000,'2026-06-01','','GS',6100,6710000,'[]');


-- ===== ROLANDO MATHIAS SIENKAWIEC SANDOVAL (id=4) =====

-- Enero 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (4,1,2026,800,0,0,0,0,5256000,'2026-02-02','','GS',6570,5256000,'[]');

-- Febrero 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (4,2,2026,800,0,0,0,0,5120000,'2026-03-02','','GS',6400,5120000,'[]');

-- Marzo 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (4,3,2026,800,0,0,0,0,5160000,'2026-04-01','','GS',6450,5160000,'[]');

-- Abril 2026 (pago em USD, sin conversión)
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (4,4,2026,800,0,0,0,0,800,'2026-05-01','','USD',0,800,'[]');

-- Mayo 2026
INSERT INTO recibos_salario
    (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
     salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
VALUES (4,5,2026,800,0,0,0,0,4880000,'2026-06-01','','GS',6100,4880000,'[]');
