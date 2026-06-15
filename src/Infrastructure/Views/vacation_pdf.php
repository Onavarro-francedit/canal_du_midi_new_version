<?php
// Variables disponibles depuis PageController::renderVacationPDF() :
// $plan, $customerName, $checkin, $checkout, $adults, $children, $reference

$datesText = '';
if (!empty($checkin)) {
    try {
        $datesText = 'du ' . (new DateTime($checkin))->format('d/m/Y');
        if (!empty($checkout)) {
            $datesText .= ' au ' . (new DateTime($checkout))->format('d/m/Y');
        }
    } catch (Throwable $e) {
        $datesText = $checkin;
    }
}
$groupText = $adults . ' adulte' . ($adults > 1 ? 's' : '');
if ($children > 0) {
    $groupText .= ' + ' . $children . ' enfant' . ($children > 1 ? 's' : '');
}
$totalDays = (int)($plan['duration_days'] ?? count($plan['days']));
$slotEmoji = ['matin' => '🌅', 'après-midi' => '☀️', 'soir' => '🌙'];
$dayColors = [
    'linear-gradient(135deg,#6a63d9,#8a7ee8)',
    'linear-gradient(135deg,#3dada5,#66c7c7)',
    'linear-gradient(135deg,#c8855a,#f1c7bc)',
    'linear-gradient(135deg,#3a80c8,#bcdff7)',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Canal du Midi — <?= htmlspecialchars($reference) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1f2340; background: #fff; }

        /* ── Header ── */
        .pdf-header {
            background: linear-gradient(135deg, #6a63d9 0%, #66c7c7 100%);
            padding: 32px 40px;
            color: #fff;
        }
        .pdf-brand { font-size: 26px; font-weight: 700; margin-bottom: 4px; }
        .pdf-tagline { opacity: .8; font-size: 14px; margin-bottom: 20px; }
        .pdf-meta-grid {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
        }
        .pdf-meta-item {}
        .pdf-meta-label {
            opacity: .7;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 3px;
        }
        .pdf-meta-value { font-size: 14px; font-weight: 600; }

        /* ── Body ── */
        .pdf-body { padding: 32px 40px; max-width: 900px; margin: 0 auto; }

        .pdf-summary {
            background: #f8f5ff;
            border-left: 4px solid #6a63d9;
            padding: 14px 18px;
            border-radius: 4px;
            font-size: 15px;
            line-height: 1.7;
            color: #333;
            margin-bottom: 32px;
        }

        /* ── Day ── */
        .pdf-day { margin-bottom: 28px; page-break-inside: avoid; }
        .pdf-day-header {
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }
        .pdf-day-num {
            background: rgba(255,255,255,.22);
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .pdf-day-label { font-size: 15px; font-weight: 600; }

        .pdf-activities {
            border: 1px solid #e8e3ff;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        /* ── Activity ── */
        .pdf-act {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 14px 20px;
            border-bottom: 1px solid #f0eeff;
        }
        .pdf-act:last-child { border-bottom: none; }

        .pdf-act-slot {
            min-width: 96px;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 700;
            color: #888;
            text-transform: capitalize;
            padding-top: 3px;
        }

        .pdf-act-content { flex: 1; }
        .pdf-act-type {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            background: rgba(106,99,217,.08);
            color: #6a63d9;
            padding: 2px 8px;
            border-radius: 100px;
            margin-bottom: 5px;
        }
        .pdf-act-title { font-size: 15px; font-weight: 700; color: #1f2340; margin-bottom: 6px; }
        .pdf-act-detail { font-size: 13px; color: #666; line-height: 1.9; }
        .pdf-act-detail span { display: block; }
        .pdf-act-price { font-weight: 700; color: #6a63d9 !important; }
        .pdf-act-note { font-size: 12px; color: #999; font-style: italic; margin-top: 5px; }

        /* ── Footer ── */
        .pdf-footer {
            margin-top: 40px;
            padding: 20px 0 0;
            border-top: 1px solid #e8e3ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #bbb;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* ── Print button ── */
        .print-fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #6a63d9;
            color: #fff;
            border: none;
            padding: 14px 22px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 28px rgba(106,99,217,.4);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: Arial, sans-serif;
            transition: transform .2s, box-shadow .2s;
        }
        .print-fab:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(106,99,217,.5); }

        @media print {
            .print-fab { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .pdf-day { page-break-inside: avoid; }
            .pdf-header { -webkit-print-color-adjust: exact; }
            .pdf-day-header { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="pdf-header">
    <div class="pdf-brand">Canal du Midi</div>
    <div class="pdf-tagline">Votre plan de voyage personnalisé — Réf. <?= htmlspecialchars($reference) ?></div>
    <div class="pdf-meta-grid">
        <div class="pdf-meta-item">
            <div class="pdf-meta-label">Voyageur</div>
            <div class="pdf-meta-value"><?= htmlspecialchars($customerName) ?></div>
        </div>
        <?php if ($datesText): ?>
        <div class="pdf-meta-item">
            <div class="pdf-meta-label">Dates</div>
            <div class="pdf-meta-value"><?= htmlspecialchars($datesText) ?></div>
        </div>
        <?php endif; ?>
        <div class="pdf-meta-item">
            <div class="pdf-meta-label">Groupe</div>
            <div class="pdf-meta-value"><?= htmlspecialchars($groupText) ?></div>
        </div>
        <div class="pdf-meta-item">
            <div class="pdf-meta-label">Durée</div>
            <div class="pdf-meta-value"><?= $totalDays ?> jour<?= $totalDays > 1 ? 's' : '' ?></div>
        </div>
    </div>
</div>

<div class="pdf-body">

    <?php if (!empty($plan['summary'])): ?>
    <div class="pdf-summary"><?= htmlspecialchars($plan['summary']) ?></div>
    <?php endif; ?>

    <?php foreach ($plan['days'] as $di => $day): ?>
    <div class="pdf-day">
        <div class="pdf-day-header" style="background:<?= $dayColors[$di % count($dayColors)] ?>;">
            <span class="pdf-day-num">Jour <?= (int)$day['day'] ?></span>
            <span class="pdf-day-label"><?= htmlspecialchars($day['label'] ?? '') ?></span>
        </div>
        <div class="pdf-activities">
            <?php foreach ($day['activities'] as $act): ?>
            <div class="pdf-act">
                <div class="pdf-act-slot">
                    <?= ($slotEmoji[$act['slot'] ?? ''] ?? '📍') . ' ' . htmlspecialchars(ucfirst($act['slot'] ?? '')) ?>
                </div>
                <div class="pdf-act-content">
                    <?php if (!empty($act['type'])): ?>
                    <span class="pdf-act-type"><?= htmlspecialchars($act['type']) ?></span>
                    <?php endif; ?>
                    <div class="pdf-act-title"><?= htmlspecialchars($act['title'] ?? '') ?></div>
                    <div class="pdf-act-detail">
                        <?php if (!empty($act['address'])): ?><span>📍 <?= htmlspecialchars($act['address']) ?></span><?php endif; ?>
                        <?php if (!empty($act['phone'])): ?><span>📞 <?= htmlspecialchars($act['phone']) ?></span><?php endif; ?>
                        <?php if (!empty($act['email'])): ?><span>✉ <?= htmlspecialchars($act['email']) ?></span><?php endif; ?>
                        <?php if (!empty($act['price'])): ?><span class="pdf-act-price">💰 <?= htmlspecialchars($act['price']) ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($act['note'])): ?>
                    <div class="pdf-act-note">💡 <?= htmlspecialchars($act['note']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="pdf-footer">
        <span>Canal du Midi — canaldumidi.fr</span>
        <span>Réf. <?= htmlspecialchars($reference) ?> — Manifestation d'intérêt, non une réservation confirmée.</span>
    </div>

</div>

<button class="print-fab" onclick="window.print()">🖨 Imprimer / Sauvegarder PDF</button>

</body>
</html>
