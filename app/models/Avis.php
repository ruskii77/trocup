<?php
class Avis
{
    public static function recus(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(
            'SELECT a.*, u.pseudo AS pseudo_auteur, u.avatar AS avatar_auteur FROM avis a JOIN utilisateur u ON u.id = a.id_auteur
             WHERE a.id_destinataire = ? ORDER BY a.date DESC'
        );
        $req->execute([$idUtilisateur]);
        return $req->fetchAll();
    }

    public static function dejaDonne(int $idProposition, int $idAuteur): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM avis WHERE id_proposition = ? AND id_auteur = ?');
        $req->execute([$idProposition, $idAuteur]);
        return $req->fetchColumn() > 0;
    }

    public static function creer(int $idProposition, int $idAuteur, int $idDestinataire, int $note, string $commentaire): void
    {
        $req = Database::get()->prepare(
            'INSERT INTO avis (id_proposition, id_auteur, id_destinataire, note, commentaire) VALUES (?, ?, ?, ?, ?)'
        );
        $req->execute([$idProposition, $idAuteur, $idDestinataire, $note, $commentaire]);
    }
}
