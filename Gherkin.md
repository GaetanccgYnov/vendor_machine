ETANT DONNE une machine a café
QUAND on insère une pièce de 50cts ou plus
ALORS le brewer reçoit l'ordre de faire un café
CAS 50cts, 1€, 2€

ETANT DONNE une machine a café
QUAND on insère une pièce moins de 50cts
ALORS le brewer ne reçoit pas d'ordre
ET l'argent est restitué
CAS 1cts, 2cts, 5cts, 10cts, 20cts

ETANT DONNE une machine a café défaillante
QUAND on insère une pièce de 50cts ou plus
ALORS l'argent est restitué

ETANT DONNE une machine a café
ALORS le brewer ne reçoit pas d'ordre

ETANT DONNE une machine a café
QUAND on insère une pièce de 50cts deux fois
ALORS le brewer reçoit deux fois l'ordre de faire un café

Il faut remplacer les noms par fake, dummy, stub, mock, spy là ou c'est necessaire pour les tests

Ajout feature : 
ETANT DONNE une machine a café ayant une dose de café
QUAND on insère le montant d'un café 2 fois
ALORS le brewer reçoit l'ordre de servir 2 cafés
ET l'argent d'un seul est encaissé
ET l'argent de l'autre est restitué

# Feature : Paiement CB
ETANT DONNE une machine à café
QUAND on insère une carte bancaire 
ET que le paiement est accepté
ALORS le brewer reçoit l'ordre de faire un café

ETANT DONNE une machine à café
QUAND on insère une carte bancaire
ET que le paiement est refusé
ALORS le brewer ne reçoit pas d'ordre

ETANT DONNE une machine à café
QUAND on insère une carte bancaire
ET que le paiement est accepté
ET que le café n'est pas disponible
ALORS le paiement est annulé 
ET l'argent est restitué

# Feature : Paiement monnaie
ETANT DONNE une machine à café avec un stock de pièces
QUAND on insère plus de 50cts en pièces
ALORS le brewer reçoit l'ordre de faire un café
ET l'argent est encaissé
ET l'argent en excès est restitué en priorité avec les pièces de plus grande valeur
ET le stock de pièces est mis à jour
SI le stock de pièces est insuffisant pour rendre la monnaie
ALORS on ne rend pas la monnaie

ETANT DONNE une machine à café
QUAND on insère plus de 5 pièces
ALORS le brewer ne reçoit pas d'ordre
ET l'argent est restitué

