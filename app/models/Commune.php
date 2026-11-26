<?php
// =====================================================
//  Communes de France avec leur point GPS (table remplie par la migration 009)
//  Sert à :
//    - proposer la bonne ville quand on tape son code postal (inscription, Mon compte)
//    - placer chaque membre (et ses annonces) sur la carte
//    - la recherche « autour de… » dans un rayon de 0 à 200 km
// =====================================================
class Commune
{
    // « Saint-Guilhem-le-Désert » → « saint guilhem le desert » (même règle que la colonne nom_recherche)
    public static function normaliser(string $texte): string
    {
        $texte = str_replace('-', ' ', slug($texte, 200));
        $texte = preg_replace('/\bst\b/', 'saint', $texte);
        return preg_replace('/\bste\b/', 'sainte', $texte);
    }

    // Les communes d'un code postal, la plus peuplée en premier
    public static function pourCodePostal(string $cp): array
    {
        if (!preg_match('/^\d{5}$/', $cp)) {
            return [];
        }
        $req = Database::get()->prepare(
            'SELECT nom, code_postal, departement, MIN(latitude) AS latitude, MIN(longitude) AS longitude, MAX(population) AS population
             FROM commune WHERE code_postal = ? GROUP BY nom, code_postal, departement ORDER BY population DESC, nom'
        );
        $req->execute([$cp]);
        return $req->fetchAll();
    }

    // La commune qui correspond au code postal + nom de ville tapés (accents, tirets, majuscules ignorés).
    // Si le code postal n'a qu'une commune, c'est elle. Sinon null (la ville ne correspond pas).
    public static function trouver(string $cp, string $ville): ?array
    {
        $communes = self::pourCodePostal($cp);
        $cherche = self::normaliser($ville);
        foreach ($communes as $c) {
            if (self::normaliser($c['nom']) === $cherche) {
                return $c;
            }
        }
        return count($communes) === 1 ? $communes[0] : null;
    }

    // Suggestions pour le champ « Autour de » : un code postal ou le début d'un nom de ville
    public static function rechercher(string $texte, int $limite = 8): array
    {
        $texte = trim($texte);
        // « Sète (34200) » : le texte d'une suggestion déjà choisie
        if (preg_match('/^(.+?)\s*\((\d{5})\)$/u', $texte, $m)) {
            $c = self::trouver($m[2], $m[1]);
            return $c ? [$c] : [];
        }
        if (preg_match('/^\d{5}$/', $texte)) {
            return array_slice(self::pourCodePostal($texte), 0, $limite);
        }
        $cherche = self::normaliser($texte);
        if (mb_strlen($cherche) < 2) {
            return [];
        }
        $req = Database::get()->prepare(
            'SELECT nom, code_postal, departement, latitude, longitude FROM commune
             WHERE nom_recherche LIKE ? ORDER BY (nom_recherche = ?) DESC, population DESC LIMIT ' . (int) $limite
        );
        $req->execute([addcslashes($cherche, '%_\\') . '%', $cherche]);
        return $req->fetchAll();
    }

    // La commune la plus proche d'un point GPS (bouton « 📍 Ma position »)
    public static function plusProche(float $lat, float $lon): ?array
    {
        $req = Database::get()->prepare(
            'SELECT nom, code_postal, departement, latitude, longitude FROM commune
             WHERE latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?
             ORDER BY POW(latitude - ?, 2) + POW((longitude - ?) * COS(RADIANS(?)), 2), population DESC LIMIT 1'
        );
        $req->execute([$lat - 0.5, $lat + 0.5, $lon - 0.7, $lon + 0.7, $lat, $lon, $lat]);
        return $req->fetch() ?: null;
    }

    // Texte affiché : « Sète (34200) »
    public static function libelle(array $c): string
    {
        return $c['nom'] . ' (' . $c['code_postal'] . ')';
    }

    // Distance en km entre deux points GPS (formule de haversine)
    public static function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // Un point GPS plausible (France métropolitaine et outre-mer)
    public static function pointValide($lat, $lon): bool
    {
        return is_numeric($lat) && is_numeric($lon) && $lat >= -23 && $lat <= 52 && $lon >= -63 && $lon <= 56;
    }
}
