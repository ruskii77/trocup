<?php
// =====================================================
//  Petites réponses JSON pour les champs de localisation (appelées par js/site.js)
//    geo/communes?cp=34200      → les villes d'un code postal (inscription, Mon compte)
//    geo/recherche?q=sete       → suggestions pour « Autour de » (page Chercher)
//    geo/autour?lat=…&lon=…     → la ville la plus proche d'une position (bouton « 📍 Ma position »)
//  La position du navigateur n'est jamais enregistrée : elle sert juste à trouver la ville.
// =====================================================
class GeoController extends Controller
{
    public function communes(): void
    {
        $this->json(array_map(fn($c) => ['nom' => $c['nom']], Commune::pourCodePostal((string) ($_GET['cp'] ?? ''))));
    }

    public function recherche(): void
    {
        $this->json(array_map(fn($c) => [
            'libelle' => Commune::libelle($c),
            'lat'     => (float) $c['latitude'],
            'lon'     => (float) $c['longitude'],
        ], Commune::rechercher(mb_substr((string) ($_GET['q'] ?? ''), 0, 60))));
    }

    public function autour(): void
    {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        $c = Commune::pointValide($lat, $lon) ? Commune::plusProche((float) $lat, (float) $lon) : null;
        $this->json($c ? ['libelle' => Commune::libelle($c)] : ['libelle' => null]);
    }

    private function json(array $donnees): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: public, max-age=86400'); // les communes ne changent pas : le navigateur peut garder la réponse
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    }
}
