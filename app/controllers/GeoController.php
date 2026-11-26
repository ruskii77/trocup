<?php
// =====================================================
//  Petites réponses JSON pour les champs de localisation (appelées par js/site.js)
//    geo/communes?cp=34200      → les villes d'un code postal (inscription, Mon compte)
//    geo/recherche?q=sete       → suggestions pour « Autour de » (page Chercher)
//    geo/autour (POST lat, lon) → la ville la plus proche d'une position (bouton « 📍 Ma position »)
//  La position du navigateur n'est jamais enregistrée : elle sert juste à trouver la ville.
//  Elle arrive en POST (donc absente des journaux du serveur et de l'historique du navigateur), et la réponse ne
//  contient que le centre de la commune : c'est lui, pas la position exacte, qui sert ensuite à la recherche.
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
        $lat = $_POST['lat'] ?? null;
        $lon = $_POST['lon'] ?? null;
        $c = ($_SERVER['REQUEST_METHOD'] === 'POST' && Commune::pointValide($lat, $lon)) ? Commune::plusProche((float) $lat, (float) $lon) : null;
        header('Cache-Control: no-store');
        $this->json($c
            ? ['libelle' => Commune::libelle($c), 'lat' => (float) $c['latitude'], 'lon' => (float) $c['longitude']]
            : ['libelle' => null]);
    }

    private function json(array $donnees): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!headers_sent() && !in_array('Cache-Control: no-store', headers_list(), true)) {
            header('Cache-Control: public, max-age=86400'); // les communes ne changent pas : le navigateur peut garder la réponse
        }
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    }
}
