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

writeFileSync(
    new URL('../canal-home/assets/home.css', import.meta.url),
    '/* GENERADO por wp-plugin/build/build-css.mjs — no editar a mano */\n' + result.css,
);
console.log('home.css OK');
