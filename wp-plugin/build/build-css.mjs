// Genera canal-home/assets/home.css: styles.css local + home-extra.css, todo bajo .cdm-home.
// Uso: node wp-plugin/build/build-css.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import postcss from 'postcss';
import prefixer from 'postcss-prefix-selector';

const PREFIX = '.cdm-home';
const read = (rel) => readFileSync(new URL(rel, import.meta.url), 'utf8');
const source = read('../../public/assets/css/styles.css') + '\n' + read('./home-extra.css');

const result = await postcss([
    prefixer({
        prefix: PREFIX,
        transform(prefix, selector, prefixed) {
            if (selector === ':root' || selector === 'html' || selector === 'body') return prefix;
            if (selector.startsWith(PREFIX)) return selector;
            return prefixed;
        },
    }),
    // El tema fija html{font-size:10px}; la home local se diseñó con 16px → rem a px fijos.
    {
        postcssPlugin: 'rem-to-px',
        Declaration(decl) {
            decl.value = decl.value.replace(/(\d*\.?\d+)rem\b/g, (_, n) => `${+(parseFloat(n) * 16).toFixed(2)}px`);
        },
    },
]).process(source, { from: undefined });

// Verificación: ninguna regla fuera de @keyframes puede escapar del contenedor.
const leaks = [];
postcss.parse(result.css).walkRules((rule) => {
    if (rule.parent?.type === 'atrule' && /keyframes$/.test(rule.parent.name)) return;
    for (const sel of rule.selectors) {
        if (!sel.startsWith(PREFIX)) leaks.push(sel);
    }
});
if (leaks.length) {
    console.error('Selectores sin prefijo:', leaks);
    process.exit(1);
}

// Verificación: el tema fija html{font-size:10px} → ningún valor puede depender de rem.
const remDecls = [];
postcss.parse(result.css).walkDecls((decl) => {
    if (/\d(\.\d+)?rem\b/.test(decl.value)) remDecls.push(`${decl.parent.selector} { ${decl.prop}: ${decl.value} }`);
});
if (remDecls.length) {
    console.error('Valores en rem (dependen del font-size raíz del tema):', remDecls.slice(0, 5), `(${remDecls.length})`);
    process.exit(1);
}

// Verificación: neutralizar el clearfix de Bootstrap del tema y el color de titulares del tema.
for (const needle of ['.cdm-home .container::before', '.cdm-home h1', '.cdm-home p', '.cdm-home:not(.js-reveal) [data-reveal]']) {
    if (!result.css.includes(needle)) {
        console.error('Falta el ajuste de compatibilidad con el tema:', needle);
        process.exit(1);
    }
}

writeFileSync(
    new URL('../canal-home/assets/home.css', import.meta.url),
    '/* GENERADO por wp-plugin/build/build-css.mjs — no editar a mano */\n' + result.css,
);
console.log('home.css OK');
