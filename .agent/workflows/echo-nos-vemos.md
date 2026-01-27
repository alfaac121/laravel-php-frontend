---
description: Actualizar README y subir a GitHub cuando el usuario diga la palabra clave
---

# Workflow: Echo Nos Vemos Mañana

## Palabra clave de activación
Cuando el usuario diga: **"echo nos vemos mñn"**

## Pasos a ejecutar

1. Actualizar el archivo `README_ACTUALIZACION.md` con un resumen de los cambios realizados en la sesión actual
   - Incluir fecha
   - Listar cambios principales de forma breve
   - Formato tipo changelog

2. Hacer git add de todos los cambios:
```bash
git add .
```

3. Hacer commit con mensaje breve:
```bash
git commit -m "✨ [Fecha] - Resumen breve de cambios"
```

4. Push al repositorio:
```bash
git push origin main
```

## Formato del README_ACTUALIZACION.md

```markdown
# 📋 Registro de Actualizaciones - Tu Mercado SENA

## [Fecha] - Título breve
### Cambios realizados:
- Cambio 1
- Cambio 2
- Cambio 3

---
(Secciones anteriores se mantienen abajo)
```

## Notas
- El commit debe ser breve, no más de 50-70 caracteres
- Solo ejecutar cuando el usuario diga EXACTAMENTE la palabra clave
- NO subir ni hacer commit sin la palabra clave
