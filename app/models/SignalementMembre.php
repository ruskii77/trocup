<?php
class SignalementMembre
{
    public static function creer(int $idSignale, int $idAuteur, string $motif): void
    {
        Database::get()->prepare('INSERT INTO signalement_membre (id_signale, id_auteur, motif) VALUES (?, ?, ?)')
            ->execute([$idSignale, $idAuteur, $motif]);
    }

    public static function enAttente(): array
    {
        return Database::get()->query(
            "SELECT s.*, signale.pseudo AS pseudo_signale, signale.avatar AS avatar_signale, signale.est_banni,
                    auteur.pseudo AS pseudo_auteur
             FROM signalement_membre s
             JOIN utilisateur signale ON signale.id = s.id_signale
             JOIN utilisateur auteur ON auteur.id = s.id_auteur
             WHERE s.statut = 'en_attente' ORDER BY s.date ASC"
        )->fetchAll();
    }

    public static function traiter(int $id): void
    {
        Database::get()->prepare("UPDATE signalement_membre SET statut = 'traite' WHERE id = ?")->execute([$id]);
    }

    public static function traiterPourMembre(int $idSignale): void
    {
        Database::get()->prepare("UPDATE signalement_membre SET statut = 'traite' WHERE id_signale = ?")->execute([$idSignale]);
    }
}
