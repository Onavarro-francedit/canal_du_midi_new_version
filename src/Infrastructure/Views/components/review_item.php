<article class="review-card">
    <div class="review-accent"></div>
    <div class="review-header">
        <div class="review-author-wrap">
            <div class="review-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div>
                <span class="review-author"><?= htmlspecialchars($review->customerName) ?></span>
                <span class="review-date"><?= date('d M Y', strtotime($review->date)) ?></span>
            </div>
        </div>
        <div class="review-stars"><?= $review->getStarsHtml() ?></div>
    </div>
    <p class="review-text">"<?= htmlspecialchars($review->comment) ?>"</p>
    <div class="review-footer">
        <span class="review-badge"><i class="bi bi-shield-check"></i> Avis vérifié</span>
    </div>
</article>