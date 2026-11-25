<?php
class Message
{
    public static function deProposition(int $idProposition): array
    {
        $req = Database::get()->prepare(
            'SELECT m.*, u.pseudo, u.avatar FROM message m JOIN utilisateur u ON u.id = m.id_expediteur
             WHERE m.id_proposition = ? ORDER BY m.date_envoi ASC, m.id ASC'
        );
        $req->execute([$idProposition]);
        return $req->fetchAll();
    }

    public static function envoyer(int $idProposition, int $idExpediteur, string $contenu): void
    {
        $req = Database::get()->prepare('INSERT INTO message (id_proposition, id_expediteur, contenu) VALUES (?, ?, ?)');
        $req->execute([$idProposition, $idExpediteur, $contenu]);
    }
}
