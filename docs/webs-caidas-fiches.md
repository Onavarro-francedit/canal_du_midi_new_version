# Webs caídas de las fichas

Las fichas cuya web no funciona. **Los datos de las fichas no se modifican nunca**: en las páginas 2026 solo se deja de
mostrar el enlace « Site web » mientras la web siga caída, y vuelve solo en cuanto responde.

## Cómo funciona

- **Revisión semanal:** cada lunes a las 7:00 (WP-Cron, `canal-home/includes/webcheck.php`) se piden las webs de todas
  las fichas publicadas, como lo haría un navegador. Si una falla, se reintenta una vez antes de darla por caída.
- **Caída** = dominio inexistente, servidor que no responde, error 404/410/5xx, o página que no es la del prestatario
  (página por defecto de Apache/nginx, hosting suspendido, dominio en venta). → enlace oculto.
- **A verificar** = 401/403/429/503: protecciones anti-robots (hoteles, OVH) que un visitante sí atraviesa. → enlace visible.
- **E-mail** cada semana a agomes@francedit.com y onavarro@francedit.com: caídas (con « depuis le » y enlace para editar
  la ficha), a verificar y las que vuelven a funcionar.
- Si alguien corrige la URL en la ficha, el enlace reaparece enseguida (la ocultación va ligada a la URL revisada).
- Lanzar a mano: `wp-plugin/remote.sh wp eval 'canal_webcheck_run();'` (envía el e-mail). Estado: opción `canal_webcheck`.

## Revisión del 6 de octubre de 2026 (137 webs)

### Caídas — enlace oculto (12)

| Ficha | Web en la ficha | Problema | Web correcta encontrada (verificada) |
|---|---|---|---|
| Ville de Sallèles-d'Aude (`mairie-de-salleles-daude`) | http://www.sallelesdaude.fr | Página por defecto de Apache | https://sallelesdaude.fr/ |
| Camping municipal de Sallèles-d'Aude | http://sallelesdaude.fr/fr/decouvrir/le-camping | 404 | https://www.camping-sallelesdaude.fr/ |
| Camping de Montolieu | https://www.campingdemontolieu.com/fr | 404 | https://www.campingdemontolieu.com/ |
| Paulette location vélo Sète | https://paulette.bike/fr/index.php?controller=agence&agence_id=8 | 404 | https://paulette.bike/agences/sete/ |
| Paulette location vélo Béziers | …agence_id=7 | 404 | https://paulette.bike/agences/beziers/ |
| Paulette location vélo Narbonne | …agence_id=6 | 404 | https://paulette.bike/agences/narbonne/ |
| Port de Sérignan | http://www.port-serignan.fr | El dominio no existe | — |
| Port de Castelnaudary | http://www.capitainerie.castelnaudary-tourisme.com | El subdominio no existe | — (la oficina de turismo sí: castelnaudary-tourisme.com) |
| Port de Colombiers | http://www.colombiers.com | El dominio no existe | — (colombiers.fr es otro sitio) |
| Le Relais de Riquet (restaurante) | http://www.restaurant-relais-riquet.fr | El dominio no existe | — |
| Port de Carcassonne | http://www.port-carcassonne.com | Error 521 (servidor caído) | — |
| Port de Valras-Plage | https://www.beziers-in-mediterranee.com/…/port-de-valras-plage | Redirige a la agencia web (raccourci.fr), 404 | — |

La columna « Web correcta » es para quien gestione las fichas: corregirlas es decisión suya (desde wp-admin), no del plugin.

### A verificar — enlace visible (3)

Hôtel Première Classe Toulouse Nord-Sesquières y Hôtel Campanile Toulouse Nord-Sesquières (403 a robots; en navegador
funcionan) y La Roue qui Tourne (503: control anti-robots de OVH). Le Jardin d'Homps solo rechazaba a un robot sin cabeceras de navegador: funciona.
