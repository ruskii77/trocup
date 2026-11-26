<?php
// Envoi des emails du site.
// En mode 'fichier', rien ne part : l'email est enregistré dans storage/emails/ pour être lu en local.
// En mode 'smtp', on utilise la bibliothèque PHPMailer (dossier lib/PHPMailer).

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    // $notif (emails de notification seulement) : ['raison' => …, 'desabonnement' => lien, 'reglages' => lien]
    public static function envoyer(string $destinataire, string $sujet, string $titre, string $texte, ?string $lien = null, ?string $texteBouton = null, ?array $notif = null): bool
    {
        $html = self::gabarit($titre, $texte, $lien, $texteBouton, $notif);

        if (MAIL_MODE === 'smtp') {
            return self::envoyerSmtp($destinataire, $sujet, $html, $notif['desabonnement'] ?? null);
        }
        return self::enregistrerFichier($destinataire, $sujet, $html, $notif['desabonnement'] ?? null);
    }

    // Liste les emails enregistrés (mode fichier), du plus récent au plus ancien
    public static function emailsDeTest(): array
    {
        $fichiers = glob(DOSSIER_EMAILS . '*.json') ?: [];
        rsort($fichiers);
        return array_map(fn($f) => json_decode(file_get_contents($f), true), array_slice($fichiers, 0, 50));
    }

    private static function enregistrerFichier(string $destinataire, string $sujet, string $html, ?string $desabonnement = null): bool
    {
        $nom = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
        $donnees = ['date' => date('Y-m-d H:i:s'), 'a' => $destinataire, 'sujet' => $sujet, 'html' => $html, 'desabonnement' => $desabonnement];
        return file_put_contents(DOSSIER_EMAILS . $nom, json_encode($donnees, JSON_UNESCAPED_UNICODE)) !== false;
    }

    private static function envoyerSmtp(string $destinataire, string $sujet, string $html, ?string $desabonnement = null): bool
    {
        require_once __DIR__ . '/../../lib/PHPMailer/Exception.php';
        require_once __DIR__ . '/../../lib/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../../lib/PHPMailer/SMTP.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOTE;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_UTILISATEUR;
            $mail->Password   = SMTP_MOT_DE_PASSE;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_EXPEDITEUR, NOM_SITE);
            $mail->addAddress($destinataire);
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body    = $html;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '</p>'], "\n", $html)));
            if ($desabonnement) {
                // Gmail, Outlook… affichent alors un bouton « Se désabonner » à côté de l'expéditeur
                $mail->addCustomHeader('List-Unsubscribe', '<' . $desabonnement . '>');
                $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            }
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Erreur email : ' . $mail->ErrorInfo);
            return false;
        }
    }

    // Mise en page commune à tous les emails
    private static function gabarit(string $titre, string $texte, ?string $lien, ?string $texteBouton, ?array $notif = null): string
    {
        $pied = $notif
            ? '<p style="font-size:12px;color:#6b7370;border-top:1px solid #e3e8e5;padding-top:12px">Tu reçois cet email car les notifications « ' . e($notif['raison']) . ' » sont activées sur ton compte.<br>'
              . '<a href="' . e($notif['desabonnement']) . '" style="color:#6b7370">Ne plus recevoir ces emails</a> · '
              . '<a href="' . e($notif['reglages']) . '" style="color:#6b7370">Gérer toutes mes notifications</a></p>'
            : '<p style="font-size:12px;color:#6b7370">Si tu n\'es pas à l\'origine de cette demande, ignore cet email.</p>';
        $bouton = $lien
            ? '<p style="text-align:center;margin:28px 0"><a href="' . e($lien) . '" style="background:#1f9d6b;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:bold">' . e($texteBouton ?? 'Ouvrir') . '</a></p>'
              . '<p style="font-size:12px;color:#6b7370">Si le bouton ne marche pas, copie ce lien dans ton navigateur :<br>' . e($lien) . '</p>'
            : '';
        return '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;color:#1d2321">'
             . '<h2 style="font-size:26px;margin-bottom:0">' . str_replace('<span>', '<span style="color:#ff8a3d">', logo_texte()) . '</h2>'
             . '<h3>' . e($titre) . '</h3>'
             . '<p>' . nl2br(e($texte)) . '</p>' . $bouton . $pied . '</div>';
    }
}
