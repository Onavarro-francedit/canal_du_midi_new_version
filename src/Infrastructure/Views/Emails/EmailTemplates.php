<?php
namespace App\Infrastructure\Views\Emails;

class EmailTemplates {

    // ── Constantes de diseño ───────────────────────────────────────────────────

    private const C_PRIMARY  = '#6a63d9';
    private const C_TEAL     = '#3dada5';
    private const C_SURFACE  = '#f8f5ff';
    private const C_BORDER   = '#e8e3ff';
    private const C_TEXT     = '#1f2340';
    private const C_MUTED    = '#6b7280';
    private const C_WARNING  = '#f59e0b';

    private const DAY_COLORS = [
        ['bg' => '#6a63d9', 'light' => '#f0eeff'],
        ['bg' => '#3dada5', 'light' => '#edfafa'],
        ['bg' => '#e07b54', 'light' => '#fff2ed'],
        ['bg' => '#4a90d9', 'light' => '#edf4ff'],
    ];

    private const SLOT_STYLES = [
        'matin'       => ['bg' => '#fff7e6', 'color' => '#b45309', 'dot' => '#f59e0b', 'label' => 'Matin',       'icon' => '🌅'],
        'après-midi'  => ['bg' => '#ecfdf5', 'color' => '#065f46', 'dot' => '#10b981', 'label' => 'Après-midi', 'icon' => '☀️'],
        'soir'        => ['bg' => '#eef2ff', 'color' => '#3730a3', 'dot' => '#6a63d9', 'label' => 'Soir',        'icon' => '🌙'],
    ];

    // ── Layout principal ───────────────────────────────────────────────────────

    private static function layout(string $preheader, string $content): string {
        return '<!DOCTYPE html>'
            . '<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
            . '<title>Canal du Midi</title>'
            . '<style>'
            .   '@import url(\'https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap\');'
            .   'body{margin:0;padding:0;background:#f0eeff;}'
            .   'a{color:' . self::C_PRIMARY . ';}'
            .   'img{border:0;display:block;}'
            .   '@media only screen and (max-width:600px){'
            .     '.email-wrap{padding:16px 8px!important;}'
            .     '.email-card{border-radius:12px!important;}'
            .     '.email-body{padding:24px 20px!important;}'
            .     '.email-header{padding:28px 24px!important;}'
            .   '}'
            . '</style>'
            . '</head>'
            . '<body style="margin:0;padding:0;background:#f0eeff;font-family:\'Sora\',Arial,sans-serif;">'
            // Preheader text (hidden, shown in inbox preview)
            . '<div style="display:none;max-height:0;overflow:hidden;font-size:1px;color:#f0eeff;">'
            . htmlspecialchars($preheader)
            . '&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>'
            // Outer table
            . '<table class="email-wrap" width="100%" cellpadding="0" cellspacing="0" border="0"'
            .   ' style="background:#f0eeff;padding:40px 16px;">'
            . '<tr><td align="center" valign="top">'
            // Card
            . '<table class="email-card" width="600" cellpadding="0" cellspacing="0" border="0"'
            .   ' style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;'
            .         'overflow:hidden;box-shadow:0 8px 40px rgba(106,99,217,0.14);">'
            . $content
            // Footer
            . '<tr><td style="padding:24px 36px;background:#f8f5ff;border-top:1px solid #e8e3ff;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0">'
            . '<tr>'
            . '<td style="font-size:12px;color:#9ca3af;line-height:1.6;">'
            . '<strong style="color:#6a63d9;">Canal du Midi</strong> &mdash; Tourisme &amp; Loisirs<br>'
            . '<a href="https://www.canaldumidi.fr" style="color:#9ca3af;text-decoration:none;">www.canaldumidi.fr</a>'
            . '</td>'
            . '<td align="right" style="font-size:11px;color:#c4b8f5;">'
            . '&#9824; Canal du Midi'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    // ── Composants réutilisables ───────────────────────────────────────────────

    private static function heroHeader(
        string $eyebrow,
        string $title,
        string $subtitle = '',
        string $bgStart  = '#6a63d9',
        string $bgEnd    = '#8a7ee8'
    ): string {
        // bgcolor attribute = fallback for Outlook/Gmail that strip CSS gradients
        return '<tr><td class="email-header" bgcolor="' . $bgStart . '" style="'
            . 'background:' . $bgStart . ';'
            . 'background:-webkit-linear-gradient(135deg,' . $bgStart . ' 0%,' . $bgEnd . ' 100%);'
            . 'background:linear-gradient(135deg,' . $bgStart . ' 0%,' . $bgEnd . ' 100%);'
            . 'padding:36px 40px;">'
            // Logo pill — solid white bg, NOT rgba (rgba unsupported in Outlook)
            . '<table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">'
            . '<tr>'
            . '<td bgcolor="#ffffff" style="background:#ffffff;border-radius:8px;padding:6px 14px;">'
            . '<span style="font-size:13px;font-weight:700;color:' . $bgStart . ';letter-spacing:-0.01em;">Canal du Midi</span>'
            . '</td>'
            . '</tr>'
            . '</table>'
            // Eyebrow
            . '<p style="margin:0 0 8px;font-size:11px;font-weight:700;color:#d4d0f7;'
            .           'text-transform:uppercase;letter-spacing:.1em;">' . $eyebrow . '</p>'
            // Title
            . '<h1 style="margin:0 0 10px;font-size:24px;font-weight:800;color:#ffffff;line-height:1.3;">'
            . $title . '</h1>'
            // Subtitle
            . ($subtitle
                ? '<p style="margin:0;font-size:14px;color:#d4d0f7;line-height:1.6;">' . $subtitle . '</p>'
                : '')
            . '</td></tr>';
    }

    private static function infoGrid(array $rows): string {
        $html = '<tr><td style="padding:0 0 20px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .       ' style="border-radius:12px;overflow:hidden;border:1px solid ' . self::C_BORDER . ';">';
        foreach ($rows as $i => [$label, $value, $icon]) {
            $bg = ($i % 2 === 0) ? self::C_SURFACE : '#ffffff';
            $html .= '<tr>'
                . '<td style="padding:12px 16px;background:' . $bg . ';width:38%;border-right:1px solid ' . self::C_BORDER . ';">'
                . '<span style="font-size:15px;margin-right:8px;">' . $icon . '</span>'
                . '<span style="font-size:13px;font-weight:600;color:' . self::C_TEXT . ';">' . $label . '</span>'
                . '</td>'
                . '<td style="padding:12px 16px;background:' . $bg . ';font-size:14px;color:#374151;">' . $value . '</td>'
                . '</tr>';
        }
        return $html . '</table></td></tr>';
    }

    private static function sectionTitle(string $title): string {
        // Solid color accent bar — gradient unsupported in Outlook
        return '<tr><td style="padding:24px 0 12px;">'
            . '<table cellpadding="0" cellspacing="0" border="0">'
            . '<tr>'
            . '<td bgcolor="' . self::C_PRIMARY . '" style="width:4px;background:' . self::C_PRIMARY . ';border-radius:2px;">&nbsp;&nbsp;</td>'
            . '<td style="padding-left:12px;font-size:15px;font-weight:700;color:' . self::C_TEXT . ';">' . $title . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>';
    }

    private static function noticeBox(string $html, string $type = 'info'): string {
        $styles = [
            'info'    => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'icon' => 'ℹ️'],
            'warning' => ['bg' => '#fffbeb', 'border' => self::C_WARNING, 'icon' => '⚠️'],
            'success' => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'icon' => '✅'],
        ];
        $s = $styles[$type] ?? $styles['info'];
        return '<tr><td style="padding:0 0 20px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .       ' style="background:' . $s['bg'] . ';border-radius:10px;border:1px solid ' . $s['border'] . '22;'
            .              'border-left:3px solid ' . $s['border'] . ';">'
            . '<tr>'
            . '<td style="padding:14px 18px;font-size:13px;color:#374151;line-height:1.7;">'
            . '<span style="margin-right:6px;">' . $s['icon'] . '</span>' . $html
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>';
    }

    private static function ctaButton(string $url, string $label): string {
        // Wrap <a> in a <td> with bgcolor for Outlook MSO compatibility
        // gradient is enhancement only; solid color is the reliable fallback
        return '<tr><td align="center" style="padding:8px 0 28px;">'
            . '<table cellpadding="0" cellspacing="0" border="0">'
            . '<tr>'
            . '<td bgcolor="' . self::C_PRIMARY . '" align="center"'
            .   ' style="background:' . self::C_PRIMARY . ';border-radius:50px;mso-padding-alt:0;">'
            . '<a href="' . $url . '" target="_blank"'
            .   ' style="display:inline-block;background:' . self::C_PRIMARY . ';'
            .           'color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;'
            .           'padding:16px 36px;border-radius:50px;'
            .           'font-family:Arial,sans-serif;'
            .           'mso-padding-alt:16px 36px;">'
            . $label
            . '</a>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>';
    }

    private static function divider(): string {
        return '<tr><td style="padding:4px 0 20px;border-bottom:1px solid ' . self::C_BORDER . ';"></td></tr>';
    }

    // ── Template 1 : Notification prestataire ────────────────────────────────

    /**
     * @param array  $slots    [ ['day'=>int, 'slot'=>string, 'title'=>string], ... ]
     */
    public static function actorNotification(
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $checkin,
        string $checkout,
        string $groupText,
        array  $slots
    ): string {
        // ── Dates de séjour formatées
        $datesText = $checkin
            ? 'du ' . self::frDate($checkin) . ($checkout ? ' au ' . self::frDate($checkout) : '')
            : 'dates à confirmer';

        // ── Lignes du tableau client
        $rows = [
            ['Voyageur',    htmlspecialchars($customerName)],
            ['Séjour',      $datesText],
            ['Groupe',      htmlspecialchars($groupText)],
        ];
        if ($customerPhone) {
            $rows[] = ['Téléphone', htmlspecialchars($customerPhone)];
        }

        $infoHtml = '';
        foreach ($rows as $i => [$label, $value]) {
            $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
            $infoHtml .= '<tr>'
                . '<td style="padding:11px 16px;background:' . $bg . ';font-size:13px;font-weight:600;'
                .           'color:#6b7280;width:36%;border-right:1px solid #e5e7eb;">' . $label . '</td>'
                . '<td style="padding:11px 16px;background:' . $bg . ';font-size:14px;color:#111827;">' . $value . '</td>'
                . '</tr>';
        }

        // ── Créneaux avec date exacte calculée
        $slotsHtml = self::buildSlotsHtml($slots, $checkin);

        // ── Bloc contact (un seul accent couleur)
        $contactRows  = '<tr><td style="padding-bottom:14px;">'
            . '<span style="display:block;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px;">Adresse e-mail</span>'
            . '<a href="mailto:' . htmlspecialchars($customerEmail) . '"'
            .   ' style="font-size:17px;font-weight:700;color:' . self::C_PRIMARY . ';text-decoration:none;">'
            . htmlspecialchars($customerEmail) . '</a>'
            . '</td></tr>';

        if ($customerPhone) {
            $contactRows .= '<tr><td>'
                . '<span style="display:block;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px;">Téléphone</span>'
                . '<a href="tel:' . htmlspecialchars($customerPhone) . '"'
                .   ' style="font-size:22px;font-weight:800;color:#111827;text-decoration:none;letter-spacing:.01em;">'
                . htmlspecialchars($customerPhone) . '</a>'
                . '</td></tr>';
        }

        // ── Hero
        $hero = self::heroHeader(
            'Canal du Midi — Planificateur de voyages',
            'Nouvelle demande d\'int&eacute;r&ecirc;t',
            'Un voyageur a s&eacute;lectionn&eacute; votre &eacute;tablissement dans son itin&eacute;raire personnalis&eacute;.'
        );

        $body = '<tr><td style="padding:36px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0">'

            // Intro
            . '<tr><td style="padding-bottom:28px;">'
            . '<p style="margin:0 0 10px;font-size:15px;color:#111827;line-height:1.75;">Bonjour,</p>'
            . '<p style="margin:0;font-size:15px;color:#374151;line-height:1.75;">'
            . 'Un voyageur a compos&eacute; son s&eacute;jour sur le <strong>Canal du Midi</strong> '
            . 'et a retenu votre &eacute;tablissement dans son programme. '
            . 'Voici les informations dont vous avez besoin pour le contacter.'
            . '</p>'
            . '</td></tr>'

            // Info table
            . '<tr><td style="padding-bottom:28px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .       ' style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
            . $infoHtml
            . '</table>'
            . '</td></tr>'

            // Schedule
            . '<tr><td style="padding-bottom:28px;">'
            . '<p style="margin:0 0 10px;font-size:13px;font-weight:600;color:#6b7280;'
            .           'text-transform:uppercase;letter-spacing:.07em;">Date(s) prévue(s) pour votre établissement</p>'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .       ' style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">'
            . $slotsHtml
            . '</table>'
            . '</td></tr>'

            // Contact block
            . '<tr><td style="padding-bottom:28px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"'
            .   ' style="border:1px solid #e5e7eb;border-left:3px solid ' . self::C_PRIMARY . ';'
            .          'border-radius:0 8px 8px 0;background:#ffffff;">'
            . '<tr><td style="padding:16px 20px 4px;">'
            . '<p style="margin:0;font-size:13px;font-weight:600;color:#111827;">'
            . 'Contactez ce voyageur directement pour confirmer sa venue</p>'
            . '<p style="margin:4px 0 16px;font-size:13px;color:#6b7280;line-height:1.6;">'
            . 'Les prestataires qui r&eacute;pondent rapidement maximisent leurs chances de conversion.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:0 20px 20px;">'
            . '<table cellpadding="0" cellspacing="0" border="0">' . $contactRows . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'

            // Legal
            . '<tr><td style="border-top:1px solid #f3f4f6;padding-top:20px;">'
            . '<p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">'
            . 'Ce message est une <strong>manifestation d\'int&eacute;r&ecirc;t</strong>, '
            . 'non une r&eacute;servation confirm&eacute;e. Il vous appartient de contacter le voyageur '
            . 'pour valider les disponibilit&eacute;s.'
            . '</p>'
            . '</td></tr>'

            . '<tr><td style="padding-top:24px;font-size:14px;color:#6b7280;line-height:1.7;">'
            . 'Cordialement,<br><strong style="color:#111827;">L\'&eacute;quipe Canal du Midi</strong>'
            . '</td></tr>'

            . '</table>'
            . '</td></tr>';

        return self::layout(
            htmlspecialchars($customerName) . ' souhaite faire appel à vos services — Canal du Midi',
            $hero . $body
        );
    }

    // ── Template 2 : Confirmation voyageur ────────────────────────────────────

    public static function userConfirmation(
        string $name,
        string $reference,
        string $datesText,
        string $groupText,
        string $daysHtml,
        string $pdfUrl
    ): string {
        $hero = self::heroHeader(
            'Votre voyage est planifié ✨',
            'Votre itinéraire personnalisé est prêt !',
            'Tous les prestataires ont été notifiés. Ils vous contacteront directement pour confirmer votre séjour.',
            '#6a63d9',
            '#3dada5'
        );

        // Reference badge — bgcolor fallback, no gradient (unsupported in Gmail/Outlook)
        $refBadge = '<tr><td align="center" style="padding:28px 0 8px;">'
            . '<table cellpadding="0" cellspacing="0" border="0">'
            . '<tr>'
            . '<td bgcolor="' . self::C_PRIMARY . '" align="center"'
            .   ' style="background:' . self::C_PRIMARY . ';border-radius:50px;padding:12px 28px;">'
            . '<span style="display:block;font-size:11px;font-weight:700;color:#d4d0f7;'
            .              'text-transform:uppercase;letter-spacing:.1em;">Référence</span>'
            . '<span style="display:block;font-size:18px;font-weight:800;color:#ffffff;'
            .              'letter-spacing:.02em;margin-top:4px;font-family:Arial,sans-serif;">'
            . htmlspecialchars($reference) . '</span>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</td></tr>';

        $body = '<tr><td class="email-body" style="padding:0 36px 32px;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0">'

            . $refBadge

            // Greeting
            . '<tr><td style="padding:24px 0 20px;">'
            . '<p style="margin:0;font-size:15px;color:' . self::C_TEXT . ';line-height:1.7;">'
            . 'Bonjour <strong>' . htmlspecialchars($name) . '</strong>,<br>'
            . 'Votre itinéraire sur le <strong>Canal du Midi</strong> est prêt ! Chaque prestataire a reçu vos coordonnées et reviendra vers vous pour confirmer.'
            . '</p>'
            . '</td></tr>'

            // Info grid
            . self::infoGrid([
                ['Dates',   htmlspecialchars($datesText),  '📅'],
                ['Groupe',  htmlspecialchars($groupText),  '👥'],
            ])

            // Itinerary
            . self::sectionTitle('Votre itinéraire')
            . '<tr><td style="padding-bottom:24px;">' . $daysHtml . '</td></tr>'

            // CTA
            . self::ctaButton($pdfUrl, '📄 &nbsp; Voir &amp; imprimer mon plan')

            // Notice
            . self::noticeBox(
                'Ce plan constitue une <strong>manifestation d\'intérêt</strong>, non une réservation confirmée. '
                . 'Les prestataires vous contacteront directement pour valider les disponibilités.',
                'info'
            )

            . self::divider()

            // Sign-off
            . '<tr><td style="font-size:14px;color:' . self::C_MUTED . ';line-height:1.7;">'
            . 'Bon voyage ! 🌊<br>'
            . '<strong style="color:' . self::C_TEXT . ';">L\'équipe Canal du Midi</strong>'
            . '</td></tr>'

            . '</table>'
            . '</td></tr>';

        return self::layout(
            'Votre plan de voyage sur le Canal du Midi — Réf. ' . $reference,
            $hero . $body
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function buildDaysHtml(array $days): string {
        $colors = self::DAY_COLORS;
        $slots  = self::SLOT_STYLES;
        $html   = '';

        foreach ($days as $di => $day) {
            $col = $colors[$di % count($colors)];
            $html .= '<div style="margin-bottom:16px;border-radius:12px;overflow:hidden;'
                .             'border:1px solid ' . self::C_BORDER . ';">'
                // Day header
                . '<div style="background:' . $col['bg'] . ';padding:13px 20px;">'
                . '<table cellpadding="0" cellspacing="0" border="0"><tr>'
                . '<td style="background:rgba(255,255,255,.18);border-radius:20px;'
                .           'padding:3px 12px;font-size:11px;font-weight:700;color:#fff;'
                .           'text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;">'
                . 'Jour ' . (int)$day['day']
                . '</td>'
                . '<td style="padding-left:12px;font-size:14px;font-weight:700;color:#fff;">'
                . htmlspecialchars($day['label'] ?? '')
                . '</td>'
                . '</tr></table>'
                . '</div>';

            // Activities
            foreach ($day['activities'] as $act) {
                $slot   = strtolower($act['slot'] ?? '');
                $ss     = $slots[$slot] ?? ['bg' => '#f9fafb', 'color' => '#374151', 'dot' => '#9ca3af', 'label' => ucfirst($slot), 'icon' => '📍'];
                $html .= '<div style="padding:14px 20px;border-top:1px solid ' . self::C_BORDER . ';background:#fff;">'
                    . '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr valign="top">'
                    // Slot badge
                    . '<td style="width:100px;padding-right:12px;">'
                    . '<span style="display:inline-block;background:' . $ss['bg'] . ';color:' . $ss['color'] . ';'
                    .              'font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;'
                    .              'white-space:nowrap;">'
                    . $ss['icon'] . ' ' . $ss['label']
                    . '</span>'
                    . '</td>'
                    // Content
                    . '<td>'
                    . '<strong style="font-size:14px;color:' . self::C_TEXT . ';">'
                    . htmlspecialchars($act['title'] ?? '') . '</strong>';
                if (!empty($act['city']))  $html .= '<span style="font-size:12px;color:' . self::C_MUTED . ';margin-left:6px;">— ' . htmlspecialchars($act['city']) . '</span>';
                $html .= '<div style="margin-top:5px;">';
                if (!empty($act['phone'])) $html .= '<span style="font-size:12px;color:' . self::C_MUTED . ';margin-right:12px;">📞 ' . htmlspecialchars($act['phone']) . '</span>';
                if (!empty($act['email'])) $html .= '<span style="font-size:12px;color:' . self::C_MUTED . ';">✉️ ' . htmlspecialchars($act['email']) . '</span>';
                $html .= '</div>';
                if (!empty($act['price'])) $html .= '<div style="margin-top:4px;font-size:13px;font-weight:700;color:' . self::C_PRIMARY . ';">' . htmlspecialchars($act['price']) . '</div>';
                if (!empty($act['note']))  $html .= '<div style="margin-top:4px;font-size:12px;color:#9ca3af;font-style:italic;">' . htmlspecialchars($act['note']) . '</div>';
                $html .= '</td></tr></table></div>';
            }

            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Builds the schedule rows for the actor email.
     * @param array  $slots   [ ['day'=>int, 'slot'=>string, 'title'=>string], ... ]
     * @param string $checkin ISO date (Y-m-d) or empty string
     */
    public static function buildSlotsHtml(array $slots, string $checkin = ''): string {
        $slotLabels = ['matin' => 'Matin', 'après-midi' => 'Après-midi', 'soir' => 'Soir'];
        $html = '';
        foreach ($slots as $i => $slot) {
            $dayNum = (int)($slot['day'] ?? ($i + 1));
            $slotLabel = $slotLabels[strtolower($slot['slot'] ?? '')] ?? ucfirst($slot['slot'] ?? '');
            $title = htmlspecialchars($slot['title'] ?? '');

            // Compute exact date if checkin is provided
            if ($checkin) {
                try {
                    $date = (new \DateTime($checkin))->modify('+' . ($dayNum - 1) . ' days');
                    $dateStr = self::frDate($date->format('Y-m-d'));
                } catch (\Throwable) {
                    $dateStr = 'Jour ' . $dayNum;
                }
            } else {
                $dateStr = 'Jour ' . $dayNum;
            }

            $border = $i > 0 ? 'border-top:1px solid #e5e7eb;' : '';
            $html .= '<tr>'
                . '<td style="padding:13px 16px;' . $border . 'width:50%;border-right:1px solid #e5e7eb;'
                .           'font-size:14px;font-weight:600;color:#111827;">'
                . $dateStr
                . '</td>'
                . '<td style="padding:13px 16px;' . $border . 'font-size:14px;color:#374151;">'
                . $slotLabel . ' &mdash; ' . $title
                . '</td>'
                . '</tr>';
        }
        return $html;
    }

    private static function frDate(string $isoDate): string {
        static $days   = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
        static $months = ['janvier','février','mars','avril','mai','juin',
                          'juillet','août','septembre','octobre','novembre','décembre'];
        try {
            $d = new \DateTime($isoDate);
            return $days[(int)$d->format('w')] . ' '
                . (int)$d->format('j') . ' '
                . $months[(int)$d->format('n') - 1] . ' '
                . $d->format('Y');
        } catch (\Throwable) {
            return $isoDate;
        }
    }
}
