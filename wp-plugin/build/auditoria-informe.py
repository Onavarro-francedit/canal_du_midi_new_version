#!/usr/bin/env python3
"""Resumen de docs/data/auditoria-2026.tsv (TASK-070) para docs/auditoria-seo-geo-2026.md.
Uso: python3 wp-plugin/build/auditoria-informe.py docs/data/auditoria-2026.tsv
Imprime en Markdown: comprobaciones por tipo de página y las páginas con clics, con sus fallos.
« alt » no se puntúa: las miniaturas junto a su nombre llevan alt="" a propósito (accesibilidad).
« Clics » = Google, 12 meses (oct. 2025 – sep. 2026) de la URL actual."""
import csv
import sys
from collections import defaultdict

# Comprobación → (descripción, función que devuelve True si la página PASA). Rúbrica seo-geo, lo medible por URL.
CHECKS = {
    'title':   ('Title 39–65 car.', lambda r: 39 <= int(r['title_len'] or 0) <= 65),
    'desc':    ('Meta description 120–165 car.', lambda r: 120 <= int(r['desc_len'] or 0) <= 165),
    'h1':      ('Un solo H1', lambda r: r['h1'] == '1'),
    'canon':   ('Canonical', lambda r: r['canonical'] == '1'),
    'social':  ('OG + Twitter card', lambda r: r['og'] == '1' and r['twitter'] == '1'),
    'crumb':   ('BreadcrumbList', lambda r: r['tipo'] == 'home' or 'BreadcrumbList' in r['schema']),
    # Artículos y archivos: los escriben los clientes, sin FAQ generada (regla del 05/10) → no aplica.
    'faq':     ('FAQPage', lambda r: r['tipo'] in ('contenu-article', 'archive') or 'FAQPage' in r['schema']),
    'date':    ('dateModified', lambda r: r['modified'] != ''),
    'words':   ('≥ 300 palabras', lambda r: int(r['words'] or 0) >= 300),
    'h2q':     ('H2 en forma de pregunta', lambda r: int(r['h2_q'] or 0) > 0),
    'links':   ('≥ 3 enlaces internos', lambda r: int(r['int_links'] or 0) >= 3),
    'ext':     ('Enlace a fuente externa', lambda r: int(r['ext_links'] or 0) > 0),
    'struct':  ('Tabla o lista', lambda r: int(r['tables_lists'] or 0) > 0),
}
TYPES = ['home', 'carte', 'categorie', 'region', 'mot-cle', 'fiche', 'etapes', 'etape', 'calcul', 'planificateur',
         'contenu-page', 'contenu-article', 'archive']

rows = [r for r in csv.DictReader(open(sys.argv[1]), delimiter='\t')]
ok = [r for r in rows if r['status'] == '200']
bad = [r for r in rows if r['status'] != '200']
clicks = lambda r: int(float(r['clics'] or 0))

by_type = defaultdict(list)
for r in ok:
    by_type[r['tipo']].append(r)

print('### Comprobaciones por tipo de página (% de páginas que pasan)\n')
print('| Tipo | Páginas | Clics/año | ' + ' | '.join(c[0] for c in CHECKS.values()) + ' |')
print('|---|---|---|' + '---|' * len(CHECKS))
for t in TYPES:
    rs = by_type.get(t, [])
    if not rs:
        continue
    cells = []
    for _, (_, f) in CHECKS.items():
        pct = round(100 * sum(1 for r in rs if f(r)) / len(rs))
        cells.append(('✅ ' if pct == 100 else '⚠️ ' if pct >= 50 else '❌ ') + f'{pct} %')
    print(f'| {t} | {len(rs)} | {sum(clicks(r) for r in rs)} | ' + ' | '.join(cells) + ' |')
print(f'\nNo responden 200: {len(bad)}' + (' — ' + ', '.join(f"{r['url']} ({r['status']})" for r in bad[:15]) if bad else ''))

top = sorted((r for r in ok if clicks(r) >= int(sys.argv[2] if len(sys.argv) > 2 else 20)), key=clicks, reverse=True)
print(f'\n### Páginas con tráfico ({len(top)} URLs, ≥ {sys.argv[2] if len(sys.argv) > 2 else 20} clics/año: '
      f'{sum(clicks(r) for r in top)} clics, {round(100 * sum(clicks(r) for r in top) / max(1, sum(clicks(r) for r in ok)))} % del total)\n')
print('| Estado | URL | Tipo | Clics | Pos. | Palabras | Falla |')
print('|---|---|---|---|---|---|---|')
for r in top:
    fails = [k for k, (_, f) in CHECKS.items() if not f(r)]
    print(f"| ⬜ | `{r['url']}` | {r['tipo']} | {clicks(r)} | {r['posicion']} | {r['words']} | {', '.join(fails)} |")
