=========================================================
  SONGHO — Version HTML/CSS/JS + PHP
  Auteur : KANNINKA IYA ISAMEL • 24G2152
=========================================================

STRUCTURE DES FICHIERS
-----------------------
songho-php/
├── index.html          ← Page d'accueil (choix du mode)
├── local.html          ← Jeu local 2 joueurs (même écran, tout en JS)
├── remote.html         ← Jeu en réseau via Ajax
├── api/
│   ├── engine.php      ← Moteur de jeu Songho (logique pure PHP)
│   ├── db.php          ← Connexion base de données SQLite
│   ├── create_game.php ← POST : créer une nouvelle partie
│   ├── get_game.php    ← GET  : lire l'état de la partie (polling Ajax)
│   ├── join_game.php   ← POST : rejoindre en tant que Nord
│   └── play_move.php   ← POST : jouer un coup
└── data/               ← Créé automatiquement (base SQLite)
    └── songho.sqlite

=========================================================
  INSTALLATION
=========================================================

1. PRÉREQUIS
   - PHP 8.0 ou supérieur avec l'extension PDO SQLite
   - Un serveur web local : XAMPP, WAMP, Laragon, ou php -S

2. DÉMARRAGE RAPIDE (ligne de commande)
   Placez le dossier songho-php/ dans votre serveur web, puis :

   cd songho-php
   php -S localhost:8080

   Ouvrez ensuite : http://localhost:8080/index.html

3. AVEC XAMPP / WAMP
   - Copiez le dossier songho-php/ dans htdocs/ (XAMPP)
     ou www/ (WAMP)
   - Démarrez Apache
   - Ouvrez : http://localhost/songho-php/index.html

=========================================================
  FONCTIONNEMENT
=========================================================

VERSION LOCALE (local.html)
  • Tout se passe dans le navigateur — aucun serveur PHP requis
  • Le moteur de jeu est entièrement écrit en JavaScript
  • 2 joueurs se passent la souris sur le même écran
  • Le joueur Sud commence toujours

VERSION RÉSEAU (remote.html + PHP)
  • Le joueur 1 clique "Créer une partie" → reçoit un code à 6 chiffres
  • Il partage ce code au joueur 2 (autre navigateur / autre machine)
  • Le joueur 2 entre le code et clique "Rejoindre"
  • Chaque navigateur interroge le serveur toutes les 2 secondes (Ajax)
    pour détecter les changements d'état (polling)
  • Le moteur de jeu tourne côté serveur (PHP) pour garantir
    l'intégrité des règles

ENDPOINTS PHP (API)
  POST api/create_game.php          → { code, token, role:"south" }
  GET  api/get_game.php?code=&token= → état complet de la partie
  POST api/join_game.php  { code }  → { token, role:"north" }
  POST api/play_move.php  { code, token, pitIndex } → nouvel état

=========================================================
  BASE DE DONNÉES
=========================================================
  Par défaut : SQLite (aucune configuration nécessaire)
  Le fichier data/songho.sqlite est créé automatiquement.

  Pour utiliser MySQL à la place, modifiez api/db.php :
    $pdo = new PDO('mysql:host=localhost;dbname=songho', 'user', 'mdp');
  Et adaptez les requêtes SQL si nécessaire.

=========================================================
