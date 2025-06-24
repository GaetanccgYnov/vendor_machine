<?php
namespace App;

interface BrewerInterface
{
    /**
     * Demande à la machine de faire couler un café.
     * Si aucune dose d'eau n'était préchargée dans le bouilleur, la machine tentera d'en charger une.
     *
     * @return bool True si aucun problème, False si défaillance.
     */
    public function makeACoffee(): bool;

    /**
     * Tire une dose d'eau depuis le réservoir vers le bouilleur.
     *
     * @return bool True si une dose a été tirée avec succès,
     *              False si le bouilleur contenait déjà une dose d'eau ou si aucune dose complète n'a pu être tirée.
     */
    public function tryPullWater(): bool;

    /**
     * Ajoute une dose de lait au mélange.
     * Il est conseillé d'ajouter le lait en premier, sauf pour le cappuccino.
     *
     * @return bool True si aucun problème, False si défaillance.
     */
    public function pourMilk(): bool;

    /**
     * Ajoute une dose d'eau au mélange. Il est conseillé d'ajouter l'eau en dernier.
     * Si aucune dose d'eau n'était dans le bouilleur, la machine tentera d'en charger une.
     *
     * @return bool True si aucun problème, False si défaillance.
     */
    public function pourWater(): bool;

    /**
     * Ajoute une dose de sucre au mélange. Il est conseillé d'ajouter le sucre en premier.
     *
     * @return bool True si aucun problème, False si défaillance.
     */
    public function pourSugar(): bool;

    /**
     * Ajoute une dose de chocolat au mélange. Il est conseillé d'ajouter le chocolat
     * après le sucre mais avant les autres ingrédients.
     *
     * @return bool True si aucun problème, False si défaillance.
     */
    public function pourChocolate(): bool;
}
