<?php
// Traitement des photos envoyées : vérification, redimensionnement, compression.
// Une photo de téléphone fait souvent 4 à 8 Mo ; après traitement elle pèse ~150 Ko
// → pages beaucoup plus rapides et moins d'espace utilisé chez l'hébergeur.
class Image
{
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    // $format : 'objet' (côté le plus long ≤ 1200 px) ou 'avatar' (carré 400 × 400, recadré au centre)
    // Renvoie le chemin relatif au dossier uploads, ex : 'a1b2….jpg' ou 'avatars/c3d4….jpg'
    public static function enregistrer(array $fichier, string $format): string
    {
        $max = 'La photo ne doit pas dépasser ' . self::tailleMaxMo() . ' Mo.';
        if (in_array($fichier['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException($max); // refusée par PHP (upload_max_filesize)
        }
        if ($fichier['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('L\'envoi de la photo a échoué.');
        }
        if ($fichier['size'] > TAILLE_MAX_PHOTO) {
            throw new RuntimeException($max);
        }
        // On vérifie le VRAI type du fichier (pas son extension, qui peut mentir)
        $type = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
        if (!isset(self::TYPES[$type])) {
            throw new RuntimeException('Formats acceptés : JPG, PNG ou WEBP.');
        }

        $sousDossier = $format === 'avatar' ? 'avatars/' : '';
        if (!is_dir(DOSSIER_UPLOADS . $sousDossier)) {
            mkdir(DOSSIER_UPLOADS . $sousDossier, 0777, true);
        }
        $nom = $sousDossier . bin2hex(random_bytes(16));

        // Sans la bibliothèque GD (rare), on garde la photo telle quelle
        if (!function_exists('imagecreatetruecolor')) {
            $nom .= '.' . self::TYPES[$type];
            if (!move_uploaded_file($fichier['tmp_name'], DOSSIER_UPLOADS . $nom)) {
                throw new RuntimeException('Impossible d\'enregistrer la photo.');
            }
            return $nom;
        }

        $source = self::ouvrir($fichier['tmp_name'], $type);
        $source = self::redresser($source, $fichier['tmp_name'], $type);
        $resultat = $format === 'avatar' ? self::carre($source, 400) : self::reduire($source, 1200);

        $nom .= '.jpg';
        if (!imagejpeg($resultat, DOSSIER_UPLOADS . $nom, 82)) { // qualité 82 : invisible à l'œil, 3 à 10× plus léger
            throw new RuntimeException('Impossible d\'enregistrer la photo.');
        }
        imagedestroy($source);
        imagedestroy($resultat);
        return $nom;
    }

    // Taille maximale réelle d'une photo, en Mo : la plus petite entre celle du site et celle de PHP
    public static function tailleMaxMo(): int
    {
        $php = trim((string) ini_get('upload_max_filesize'));
        $octets = (int) $php;
        $unite = strtoupper(substr($php, -1));
        $octets *= ['G' => 1073741824, 'M' => 1048576, 'K' => 1024][$unite] ?? 1;
        return (int) floor(min(TAILLE_MAX_PHOTO, $octets ?: TAILLE_MAX_PHOTO) / 1048576);
    }

    public static function supprimer(?string $nom): void
    {
        if ($nom && !str_contains($nom, '..') && is_file(DOSSIER_UPLOADS . $nom)) {
            unlink(DOSSIER_UPLOADS . $nom);
        }
    }

    private static function ouvrir(string $chemin, string $type): GdImage
    {
        $image = match ($type) {
            'image/jpeg' => @imagecreatefromjpeg($chemin),
            'image/png'  => @imagecreatefrompng($chemin),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($chemin) : false,
        };
        if (!$image) {
            throw new RuntimeException('Cette image est illisible ou abîmée.');
        }
        return $image;
    }

    // Les photos de téléphone sont souvent enregistrées « couchées » avec une info de rotation (EXIF)
    private static function redresser(GdImage $image, string $chemin, string $type): GdImage
    {
        if ($type !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }
        $orientation = @exif_read_data($chemin)['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        return $angle ? imagerotate($image, $angle, 0) : $image;
    }

    // Copie sur fond blanc (les zones transparentes des PNG deviendraient noires en JPEG)
    private static function toile(int $largeur, int $hauteur): GdImage
    {
        $toile = imagecreatetruecolor($largeur, $hauteur);
        imagefill($toile, 0, 0, imagecolorallocate($toile, 255, 255, 255));
        return $toile;
    }

    private static function reduire(GdImage $source, int $max): GdImage
    {
        $l = imagesx($source);
        $h = imagesy($source);
        $ratio = min(1, $max / max($l, $h)); // on n'agrandit jamais une petite image
        $nl = (int) round($l * $ratio);
        $nh = (int) round($h * $ratio);
        $dest = self::toile($nl, $nh);
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $nl, $nh, $l, $h);
        return $dest;
    }

    private static function carre(GdImage $source, int $cote): GdImage
    {
        $l = imagesx($source);
        $h = imagesy($source);
        $min = min($l, $h);
        $dest = self::toile($cote, $cote);
        // on découpe le plus grand carré possible au centre de la photo
        imagecopyresampled($dest, $source, 0, 0, (int) (($l - $min) / 2), (int) (($h - $min) / 2), $cote, $cote, $min, $min);
        return $dest;
    }
}
