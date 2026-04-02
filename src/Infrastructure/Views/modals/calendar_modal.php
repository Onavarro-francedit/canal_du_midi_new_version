<!-- src/Infrastructure/Views/components/calendar_modal.php -->
<div id="calendar-modal" class="modal-overlay">
    <div class="modal-card calendar-card">
        <button type="button" class="calendar-close" aria-label="Fermer la fenêtre de sélection" onclick="closeCalendarModal()">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="calendar-intro">
            <span class="calendar-kicker">Planifier votre séjour</span>
            <h2>Choisissez vos dates</h2>
            <p>Sélectionnez une date d'arrivée puis une date de départ pour préparer votre demande de réservation.</p>
        </div>

        <div class="calendar-header">
            <button type="button" id="prev-month" class="btn-icon calendar-nav" aria-label="Mois précédent"><i class="bi bi-chevron-left"></i></button>
            <div class="calendar-month-chip">
                <span class="calendar-month-label">Mois affiché</span>
                <h3 id="current-month-display">Mois 2024</h3>
            </div>
            <button type="button" id="next-month" class="btn-icon calendar-nav" aria-label="Mois suivant"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="calendar-board">
            <div class="calendar-weekdays">
                <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
            </div>
            <div id="calendar-days" class="calendar-days"></div>
        </div>

        <div class="calendar-legend">
            <span class="legend-item"><span class="dot available"></span> Libre</span>
            <span class="legend-item"><span class="dot occupied"></span> Occupé</span>
            <span class="legend-item"><span class="dot selected"></span> Sélection</span>
        </div>

        <div class="modal-actions calendar-actions">
            <button type="button" onclick="closeCalendarModal()" class="button button-ghost">Annuler</button>
            <button type="button" id="apply-dates-btn" class="button" disabled>Confirmer les dates</button>
        </div>
    </div>
</div>