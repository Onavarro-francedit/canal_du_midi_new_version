<?php
/**
 * Plantilla « Planificateur 2026 » (TASK-044) — mockups docs/mockups/planificateur-2026.html (inicio y chat)
 * y planificateur-2026-plan.html (vista plan: chat a la izquierda, plan a la derecha).
 * Cabecera real (header.php); el contenido es una pantalla fija bajo ella (planner.js calcula su borde).
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="cdm-planner" id="cdm-planner">
 <div class="pl-stage">
  <div class="pl-wrap" id="pl-wrap">

    <section id="pl-hero" class="pl-hero">
      <div class="pl-orb pl-orb--hero" id="pl-main-orb" aria-hidden="true">
        <div class="pl-orb__glow"></div>
        <div class="pl-orb__core"><span class="pl-orb__blob pl-b1"></span><span class="pl-orb__blob pl-b2"></span><span class="pl-orb__blob pl-b3"></span><span class="pl-orb__blob pl-b4"></span><span class="pl-orb__swirl"></span><span class="pl-orb__shine"></span></div>
      </div>
      <p class="pl-hello">Bonjour, voyageur</p>
      <h1 class="pl-title">Quel séjour imaginez-vous ?</h1>
    </section>

    <section id="pl-chat" class="pl-chat" hidden>
      <div class="pl-chat-head">
        <div class="pl-chat-id">
          <div class="pl-orb pl-orb--sm" aria-hidden="true"><div class="pl-orb__glow"></div><div class="pl-orb__core"><span class="pl-orb__blob pl-b1"></span><span class="pl-orb__blob pl-b2"></span><span class="pl-orb__blob pl-b3"></span><span class="pl-orb__blob pl-b4"></span><span class="pl-orb__swirl"></span><span class="pl-orb__shine"></span></div></div>
          <div><strong>Assistant du Canal du Midi</strong><span id="pl-status">En ligne</span></div>
        </div>
        <button type="button" class="pl-ghost" id="pl-reset">Nouveau séjour</button>
        <button type="button" class="pl-close" id="pl-close" aria-label="Fermer la conversation"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      </div>
      <div id="pl-log" class="pl-log" role="log" aria-live="polite"></div>
      <div id="pl-chips" class="pl-chips" hidden></div>
    </section>

    <form id="pl-composer" class="pl-composer">
      <label for="pl-input" class="screen-reader-text">Décrivez votre séjour</label>
      <textarea id="pl-input" rows="2" maxlength="500" placeholder="Décrivez votre séjour idéal…"></textarea>
      <input type="text" name="website" id="pl-website" tabindex="-1" autocomplete="off" aria-hidden="true" class="pl-hp">
      <button type="submit" class="pl-send" aria-label="Envoyer"><svg viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
    </form>

    <section id="pl-ideas" class="pl-ideas" aria-label="Idées proposées par l'IA">
      <div class="pl-ideas-head">
        <svg viewBox="0 0 24 24"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 3v4M21 5h-4"/></svg>
        <span>L'IA vous propose</span><span class="pl-cur" id="pl-cur">…</span>
      </div>
      <div class="pl-cats" id="pl-cats"></div>
      <div class="pl-cards" id="pl-cards" aria-live="polite"></div>
    </section>

  </div>

  <section id="pl-plan" class="pl-plan" aria-labelledby="pl-plan-title" hidden></section>
 </div>

  <div class="pl-scrim" id="pl-scrim"></div>
  <div class="pl-mbar" id="pl-mbar">
    <button type="button" class="pl-mbar__ask" id="pl-mbar-ask">Ajustez votre séjour…<span class="pl-send" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg></span></button>
    <button type="button" class="pl-mbar__conv" id="pl-mbar-conv" aria-label="Voir la conversation"><svg viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg><b id="pl-mbar-count">0</b></button>
  </div>
  <template id="pl-orb-tpl">
    <div class="pl-orb pl-orb--xs" aria-hidden="true"><div class="pl-orb__glow"></div><div class="pl-orb__core"><span class="pl-orb__blob pl-b1"></span><span class="pl-orb__blob pl-b2"></span><span class="pl-orb__blob pl-b3"></span><span class="pl-orb__blob pl-b4"></span><span class="pl-orb__swirl"></span><span class="pl-orb__shine"></span></div></div>
  </template>
</div>
<?php
get_footer();
