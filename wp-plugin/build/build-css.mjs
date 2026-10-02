// Genera canal-home/assets/home.css (.cdm-home), carte.css (.cdm-carte), fiche.css (.cdm-fiche) e icons.css
// a partir del CSS de la app local. Uso: node wp-plugin/build/build-css.mjs
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import postcss from 'postcss';
import prefixer from 'postcss-prefix-selector';

const read = (rel) => readFileSync(new URL(rel, import.meta.url), 'utf8');

// El tema fija html{font-size:10px}; la app local se diseñó con 16px → rem a px fijos.
const remToPx = {
    postcssPlugin: 'rem-to-px',
    Declaration(decl) {
        decl.value = decl.value.replace(/(\d*\.?\d+)rem\b/g, (_, n) => `${+(parseFloat(n) * 16).toFixed(2)}px`);
    },
};

// search.css asume una cabecera de 82px (y 82+61 = 143px); en WP la mide el JS (--cdm-header-h).
const headerHeight = {
    postcssPlugin: 'header-height',
    Once(root) {
        root.walkDecls((decl) => {
            // Procesar cada declaración exactamente una vez
            // Primero: 143px → calc(var(--cdm-header-h, 82px) + 61px)
            if (decl.value.includes('143px')) {
                decl.value = decl.value.replace(/\b143px\b/g, 'calc(var(--cdm-header-h, 82px) + 61px)');
            }
            // Luego: 82px (que no esté en var) → var(--cdm-header-h, 82px)
            // Solo reemplazar si no está ya en una variable
            if (decl.value.includes('82px') && !decl.value.includes('--cdm-header-h')) {
                decl.value = decl.value.replace(/\b82px\b/g, 'var(--cdm-header-h, 82px)');
            }
        });
    },
};

async function build({ prefix, sources, out, plugins = [], needles }) {
    const source = sources.map(read).join('\n');
    const result = await postcss([
        prefixer({
            prefix,
            transform(p, selector, prefixed) {
                if (selector === ':root' || selector === 'html' || selector === 'body') return p;
                if (selector.startsWith(prefix)) return selector;
                return prefixed;
            },
        }),
        ...plugins,
        remToPx,
    ]).process(source, { from: undefined });

    // Verificación: ninguna regla fuera de @keyframes puede escapar del contenedor.
    const leaks = [];
    postcss.parse(result.css).walkRules((rule) => {
        if (rule.parent?.type === 'atrule' && /keyframes$/.test(rule.parent.name)) return;
        for (const sel of rule.selectors) {
            if (!sel.startsWith(prefix)) leaks.push(sel);
        }
    });
    if (leaks.length) {
        console.error(`${out}: selectores sin prefijo:`, leaks);
        process.exit(1);
    }

    // Verificación: el tema fija html{font-size:10px} → ningún valor puede depender de rem.
    const remDecls = [];
    postcss.parse(result.css).walkDecls((decl) => {
        if (/\d(\.\d+)?rem\b/.test(decl.value)) remDecls.push(`${decl.parent.selector} { ${decl.prop}: ${decl.value} }`);
    });
    if (remDecls.length) {
        console.error(`${out}: valores en rem:`, remDecls.slice(0, 5), `(${remDecls.length})`);
        process.exit(1);
    }

    // Verificación: ajustes de compatibilidad con el tema presentes.
    for (const needle of needles) {
        if (!result.css.includes(needle)) {
            console.error(`${out}: falta el ajuste de compatibilidad con el tema:`, needle);
            process.exit(1);
        }
    }

    writeFileSync(
        new URL(`../canal-home/assets/${out}`, import.meta.url),
        '/* GENERADO por wp-plugin/build/build-css.mjs — no editar a mano */\n' + result.css,
    );
    console.log(`${out} OK`);
}

await build({
    prefix: '.cdm-home',
    sources: ['../../public/assets/css/styles.css', './home-extra.css'],
    out: 'home.css',
    needles: ['.cdm-home .container::before', '.cdm-home h1', '.cdm-home p', '.cdm-home:not(.js-reveal) [data-reveal]'],
});

await build({
    prefix: '.cdm-carte',
    sources: ['../../public/assets/css/styles.css', '../../public/assets/css/search.css', './carte-extra.css'],
    out: 'carte.css',
    plugins: [headerHeight],
    needles: ['.cdm-carte .container::before', '.cdm-carte h1', '.cdm-carte p', 'var(--cdm-header-h, 82px)', '.cdm-carte .search-workspace'],
});

await build({
    prefix: '.cdm-fiche',
    sources: ['../../public/assets/css/styles.css', '../../public/assets/css/service_detail.css', './fiche-extra.css'],
    out: 'fiche.css',
    needles: ['.cdm-fiche .container::before', '.cdm-fiche h1', '.cdm-fiche p', '.cdm-fiche .service-hero', '.cdm-fiche a.category-tag'],
});

// Iconos: máscaras SVG en ::before (sin fuente ni CDN: TASK-048). Se generan solo los bi-* que usan el marcado y
// el JS del plugin; si falta el SVG de alguno, el build falla (no hay icono invisible en producción).
// ponytail: un icono que solo exista en la BD (no en el código) no entra solo — añadirlo a EXTRA_ICONS.
const EXTRA_ICONS = [];

function* walk(dir) {
    for (const name of readdirSync(dir)) {
        const full = join(dir, name);
        if (statSync(full).isDirectory()) yield* walk(full);
        else if (/\.(php|js)$/.test(name)) yield full;
    }
}

function buildIcons() {
    const pluginDir = fileURLToPath(new URL('../canal-home/', import.meta.url));
    const svgDir = fileURLToPath(new URL('./node_modules/bootstrap-icons/icons/', import.meta.url));
    const names = new Set(EXTRA_ICONS);
    for (const file of walk(pluginDir)) {
        for (const m of readFileSync(file, 'utf8').matchAll(/\bbi-([a-z0-9]+(?:-[a-z0-9]+)*)/g)) names.add(m[1]);
    }
    const missing = [];
    let css = '/* GENERADO por wp-plugin/build/build-css.mjs — no editar a mano */\n'
        + '[class^="bi-"]::before,[class*=" bi-"]::before{content:"";display:inline-block;width:1em;height:1em;vertical-align:-.125em;'
        + 'background-color:currentColor;-webkit-mask:var(--bi) center/contain no-repeat;mask:var(--bi) center/contain no-repeat}\n';
    for (const name of [...names].sort()) {
        let svg;
        try {
            svg = readFileSync(join(svgDir, `${name}.svg`), 'utf8').trim();
        } catch {
            missing.push(name);
            continue;
        }
        // < > # codificados: el data-URI nunca contiene « </style> » ni rompe el CSS en línea.
        const uri = svg.replace(/"/g, "'").replace(/</g, '%3C').replace(/>/g, '%3E').replace(/#/g, '%23').replace(/\s+/g, ' ');
        css += `.bi-${name}{--bi:url("data:image/svg+xml,${uri}")}\n`;
    }
    if (missing.length) {
        console.error('icons.css: faltan SVG de bootstrap-icons:', missing);
        process.exit(1);
    }
    writeFileSync(new URL('../canal-home/assets/icons.css', import.meta.url), css);
    console.log(`icons.css OK (${names.size} iconos)`);
}

buildIcons();
