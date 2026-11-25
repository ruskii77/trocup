<?php
// Un membre peut en bloquer un autre : plus aucune proposition ni message possible entre eux
class Blocage
{
    public static function basculer(int $bloqueur, int $bloque): bool
    {
        $pdo = Database::get();
        if (self::aBloque($bloqueur, $bloque)) {
            $pdo->prepare('DELETE FROM blocage WHERE id_bloqueur = ? AND id_bloque = ?')->execute([$bloqueur, $bloque]);
            return false;
        }
        $pdo->prepare('INSERT INTO blocage (id_bloqueur, id_bloque) VALUES (?, ?)')->execute([$bloqueur, $bloque]);
        // Les propositions en attente entre les deux sont annulées
        $pdo->prepare(
            "UPDATE proposition SET statut = 'annulee', date_reponse = NOW(6)
             WHERE statut = 'en_attente' AND ((id_proposeur = ? AND id_destinataire = ?) OR (id_proposeur = ? AND id_destinataire = ?))"
        )->execute([$bloqueur, $bloque, $bloque, $bloqueur]);
        return true;
    }

    public static function aBloque(int $bloqueur, int $bloque): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM blocage WHERE id_bloqueur = ? AND id_bloque = ?');
        $req->execute([$bloqueur, $bloque]);
        return $req->fetchColumn() > 0;
    }

    // Vrai si l'un des deux a bloqué l'autre
    public static function entre(int $a, int $b): bool
    {
        return self::aBloque($a, $b) || self::aBloque($b, $a);
    }
}
