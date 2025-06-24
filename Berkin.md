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
