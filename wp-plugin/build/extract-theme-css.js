// Genera canal-home/assets/{fiche,carte,home}-theme.css: las reglas del CSS del tema que usa cada página 2026.
// Uso: cargar la página con el CSS del tema (p. ej. su HTML de antes del cambio servido con Playwright
// page.route, o quitando temporalmente los estilos del tema de CANAL_THEME_UNUSED_ASSETS), esperar a que el JS
// termine, ejecutar esta función (consola o Playwright browser_evaluate) y guardar el resultado con la cabecera
// « GENERADO » del archivo actual.
// ponytail: selección por DOM de UNA página; si otra variante (ficha con vídeo…) necesita reglas del tema, regenerar sobre ella.
() => {
    const own = /^canal-/;
    const strip = (s) => s.replace(/::?(before|after|placeholder|selection|marker|backdrop|-webkit-[\w-]+|-moz-[\w-]+|-ms-[\w-]+)(\([^)]*\))?/g, '')
        .replace(/:(hover|focus|active|visited|focus-within|focus-visible|checked|disabled|target|link|indeterminate|invalid|valid|required|optional|read-only|placeholder-shown)\b/g, '');
    const usedSel = (sel) => sel.split(',').some((p) => { const q = strip(p).trim() || '*'; try { return !!document.querySelector(q); } catch (e) { return false; } });
    const abs = (css, base) => css.replace(/url\((['"]?)(?!data:|https?:|\/\/)([^'")]+)\1\)/g, (m, q, u) => 'url("' + new URL(u, base).href + '")');
    let out = ''; const stats = []; const fams = new Set(); const anims = new Set(); const faces = []; const keyframes = [];
    for (const sh of document.styleSheets) {
        const node = sh.ownerNode; if (!node || node.tagName !== 'LINK') continue;
        const id = (node.id || '').replace(/-css$/, ''); if (own.test(id)) continue;
        let rules; try { rules = sh.cssRules; } catch (e) { stats.push(id + ':cross-origin'); continue; }
        let kept = 0; let total = 0;
        const walk = (rs) => {
            let txt = '';
            for (const r of rs) {
                if (r.type === 5) { faces.push(abs(r.cssText, sh.href)); continue; }
                if (r.type === 7) { keyframes.push([r.name, r.cssText]); continue; }
                if (r.cssRules && !r.selectorText) { const inner = walk(r.cssRules); if (inner) txt += r.cssText.slice(0, r.cssText.indexOf('{')) + '{' + inner + '}\n'; continue; }
                if (!r.selectorText) continue;
                total++;
                if (usedSel(r.selectorText)) {
                    kept++; txt += abs(r.cssText, sh.href) + '\n';
                    const ff = r.style.getPropertyValue('font-family'); if (ff) ff.split(',').forEach((f) => fams.add(f.trim().replace(/['"]/g, '').toLowerCase()));
                    (r.style.getPropertyValue('animation-name') || '').split(',').forEach((a) => a.trim() && anims.add(a.trim()));
                }
            }
            return txt;
        };
        const body = walk(rules);
        if (body) out += '/* ' + id + ' */\n' + body;
        stats.push(id + ':' + kept + '/' + total);
    }
    const usedFaces = faces.filter((f) => { const m = f.match(/font-family:\s*([^;]+);/); return m && fams.has(m[1].trim().replace(/['"]/g, '').toLowerCase()); });
    const usedKf = keyframes.filter(([n]) => anims.has(n)).map((k) => k[1]);
    return '/* STATS ' + stats.join(' ') + ' */\n' + usedFaces.join('\n') + '\n' + usedKf.join('\n') + '\n' + out;
}
