# canal_du_midi_new_version

## Mantenimiento diario de categorías

Para recalcular una vez al día la caché de conteos de categorías, ejecuta:

```bash
php /Applications/XAMPP/xamppfiles/htdocs/canal_du_midi/script_refresh_category_counts.php
```

Ejemplo de cron diario a las 02:15:

```bash
15 2 * * * /usr/bin/php /Applications/XAMPP/xamppfiles/htdocs/canal_du_midi/script_refresh_category_counts.php >/tmp/canal_du_midi_category_counts.log 2>&1
```
