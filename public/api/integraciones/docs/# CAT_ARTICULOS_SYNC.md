# CAT_ARTICULOS_SYNC

## Descripción
Evento de sincronización del catálogo de artículos desde un sistema externo
hacia el WMS.

Este evento NO crea reglas de negocio.  
Las reglas se controlan desde configuración interna.

---

## Endpoint
POST /api/integraciones/contratos/v1/catalogos/articulos/sync

---

## Headers requeridos
Content-Type: application/json  
Accept: application/json  

---

## Request (estructura canónica)

```json
{
  "sistema": "ERP_EXTERNO",
  "cliente_id": 1,
  "referencia": "SYNC_YYYYMMDD_001",
  "usuario": "INTEGRACION",
  "dispositivo": "ERP",
  "items": [
    {
      "sku": "ART-001",
      "descripcion": "Producto ejemplo",
      "unidad": "PZA",
      "activo": 1
    }
  ]
}
