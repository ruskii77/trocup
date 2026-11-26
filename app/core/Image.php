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
        $chemin = $fichier['tmp_name'];
        $type = (new finfo(FILEINFO_MIME_TYPE))->file($chemin);
        // Photo iPhone (HEIC) arrivée telle quelle (navigateur sans JavaScript…) : on tente la conversion sur le serveur
        if (self::estHeic($chemin, $type)) {
            $chemin = self::heicVersJpeg($chemin);
            $type = 'image/jpeg';
        }
        if (!isset(self::TYPES[$type])) {
            throw new RuntimeException('Formats acceptés : JPG, PNG, WEBP ou HEIC (iPhone).');
        }

        $sousDossier = $format === 'avatar' ? 'avatars/' : '';
        if (!is_dir(DOSSIER_UPLOADS . $sousDossier)) {
            mkdir(DOSSIER_UPLOADS . $sousDossier, 0777, true);
        }
        $nom = $sousDossier . bin2hex(random_bytes(16));

        // Sans la bibliothèque GD (rare), on garde la photo telle quelle
        if (!function_exists('imagecreatetruecolor')) {
            $nom .= '.' . self::TYPES[$type];
            if (!($chemin === $fichier['tmp_name'] ? move_uploaded_file($chemin, DOSSIER_UPLOADS . $nom) : rename($chemin, DOSSIER_UPLOADS . $nom))) {
                throw new RuntimeException('Impossible d\'enregistrer la photo.');
            }
            return $nom;
        }

        $source = self::ouvrir($chemin, $type);
        $source = self::redresser($source, $chemin, $type);
        $resultat = $format === 'avatar' ? self::carre($source, 400) : self::reduire($source, 1200);

        $nom .= '.jpg';
        if (!imagejpeg($resultat, DOSSIER_UPLOADS . $nom, 82)) { // qualité 82 : invisible à l'œil, 3 à 10× plus léger
            throw new RuntimeException('Impossible d\'enregistrer la photo.');
        }
        imagedestroy($source);
        imagedestroy($resultat);
        if ($chemin !== $fichier['tmp_name'] && is_file($chemin)) {
            unlink($chemin); // JPEG temporaire issu d'une photo HEIC
        }
        return $nom;
    }

    // HEIC/HEIF = format photo des iPhone. Reconnu par son type, ou par sa « signature » (octets 4 à 12 : ftypheic, ftypmif1…)
    private static function estHeic(string $chemin, string $type): bool
    {
        if (in_array($type, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true)) {
            return true;
        }
        $debut = (string) file_get_contents($chemin, false, null, 4, 8);
        return in_array($debut, ['ftypheic', 'ftypheix', 'ftyphevc', 'ftypheim', 'ftypheis', 'ftypmif1', 'ftypmsf1'], true);
    }

    // Conversion HEIC → JPEG sur le serveur, possible seulement si l'extension PHP Imagick sait lire le HEIC.
    // Normalement inutile : js/site.js convertit déjà la photo dans le navigateur avant l'envoi.
    private static function heicVersJpeg(string $chemin): string
    {
        if (!class_exists('Imagick') || !Imagick::queryFormats('HEIC')) {
            throw new RuntimeException('Cette photo iPhone (HEIC) n\'a pas pu être convertie. Réessaie depuis un navigateur à jour, '
                . 'ou exporte-la en JPG (sur iPhone : Réglages → Appareil photo → Formats → « Le plus compatible »).');
        }
        try {
            $image = new Imagick($chemin);
            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient(); // photo prise de côté → remise droite
            }
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(92);
            $sortie = tempnam(sys_get_temp_dir(), 'heic') . '.jpg';
            $image->writeImage($sortie);
            $image->clear();
            return $sortie;
        } catch (ImagickException $e) {
            throw new RuntimeException('Cette photo iPhone (HEIC) est illisible. Essaie avec une autre photo ou exporte-la en JPG.');
        }
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
    // Photo prise téléphone en main : l'appareil enregistre l'image « couchée » + une étiquette EXIF « Orientation ».
    // On applique cette étiquette pour que la photo s'affiche droite (même sans l'extension PHP exif).
    private static function redresser(GdImage $image, string $chemin, string $type): GdImage
    {
        if ($type !== 'image/jpeg') {
            return $image;
        }
        $orientation = self::orientationExif($chemin);
        // 2, 4, 5, 7 = photo en miroir (caméra selfie) : on retourne d'abord l'image
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        // imagerotate tourne dans le sens inverse des aiguilles d'une montre
        $angle = [3 => 180, 4 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
        if (!$angle) {
            return $image;
        }
        $tournee = imagerotate($image, $angle, 0);
        imagedestroy($image);
        return $tournee;
    }

    // Lit l'étiquette EXIF « Orientation » (1 à 8) d'un JPEG, directement dans le fichier.
    // Structure : marqueurs JPEG → bloc APP1 « Exif » → en-tête TIFF (ordre des octets II ou MM) → répertoire IFD0 → étiquette 0x0112.
    public static function orientationExif(string $chemin): int
    {
        if (function_exists('exif_read_data')) {
            $o = (int) (@exif_read_data($chemin)['Orientation'] ?? 0);
            if ($o >= 1 && $o <= 8) {
                return $o;
            }
        }
        $f = @fopen($chemin, 'rb');
        if (!$f) {
            return 1;
        }
        $orientation = 1;
        if (fread($f, 2) === "\xFF\xD8") {                       // début d'un JPEG
            while (!feof($f)) {
                $marqueur = fread($f, 2);
                if (strlen($marqueur) < 2 || $marqueur[0] !== "\xFF") {
                    break;
                }
                $taille = unpack('n', fread($f, 2))[1] ?? 0;
                if ($marqueur === "\xFF\xE1" && $taille > 8) {      // APP1 : peut contenir l'EXIF
                    $bloc = fread($f, $taille - 2);
                    if (substr($bloc, 0, 6) === "Exif\0\0") {
                        $tiff = substr($bloc, 6);
                        $le = substr($tiff, 0, 2) === 'II';          // II = little-endian, MM = big-endian
                        $u16 = fn($pos) => unpack($le ? 'v' : 'n', substr($tiff, $pos, 2))[1] ?? 0;
                        $u32 = fn($pos) => unpack($le ? 'V' : 'N', substr($tiff, $pos, 4))[1] ?? 0;
                        $ifd = $u32(4);
                        $n = $u16($ifd);
                        for ($i = 0; $i < $n && $ifd + 2 + ($i + 1) * 12 <= strlen($tiff); $i++) {
                            $entree = $ifd + 2 + $i * 12;
                            if ($u16($entree) === 0x0112) {
                                $o = $u16($entree + 8);
                                $orientation = ($o >= 1 && $o <= 8) ? $o : 1;
                                break;
                            }
                        }
                    }
                    break;
                }
                if ($marqueur === "\xFF\xDA" || $taille < 2) {      // début de l'image : pas d'EXIF trouvé
                    break;
                }
                fseek($f, $taille - 2, SEEK_CUR);
            }
        }
        fclose($f);
        return $orientation;
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
