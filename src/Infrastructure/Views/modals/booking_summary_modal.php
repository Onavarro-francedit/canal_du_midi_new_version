<!-- src/Infrastructure/Views/components/booking_summary_modal.php -->
<div id="summary-modal" class="modal-overlay">
    <div class="modal-card summary-card">
        <div class="modal-header">
            <h2>Récapitulatif de votre séjour</h2>
            <p>Vérifiez vos informations avant de confirmer la demande.</p>
        </div>

        <div class="summary-details">
            <div class="summary-item">
                <span class="label">Établissement</span>
                <strong id="sum-title"><?= htmlspecialchars($service->translations['title']) ?></strong>
            </div>
            
            <div class="summary-row">
                <div class="summary-item">
                    <span class="label"><i class="bi bi-calendar-check"></i> Arrivée</span>
                    <strong id="sum-checkin">-</strong>
                </div>
                <div class="summary-item">
                    <span class="label"><i class="bi bi-calendar-x"></i> Départ</span>
                    <strong id="sum-checkout">-</strong>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-item">
                    <span class="label"><i class="bi bi-people"></i> Voyageurs</span>
                    <strong id="sum-guests">-</strong>
                </div>
                <div class="summary-item">
                    <span class="label"><i class="bi bi-tag"></i> Prix Total</span>
                    <strong id="sum-total" class="text-primary">-</strong>
                </div>
            </div>

            <div id="sum-special" class="summary-special is-hidden">
                <span class="label">Besoins spécifiques</span>
                <p id="sum-special-text"></p>
            </div>
        </div>

        <div class="modal-actions">
            <button type="button" onclick="closeSummaryModal()" class="button button-ghost">Modifier</button>
            <button type="button" id="confirm-booking-btn" class="button">Confirmer la réservation</button>
        </div>
    </div>
</div>