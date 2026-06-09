# Escape the ISS -- Salle de Stockage (G5E)

Projet de fin d'annee ISEP A1 -- Escape Game Connecte sur le theme de la Station Spatiale Internationale.

## Le scenario

> **/// ALERTE GENERALE -- PRIORITE ABSOLUE ///**
>
> Une bande d'aliens a infiltre l'ISS et a derobe l'integralite du stock de brioches.
> D'apres nos analystes, ces creatures souffrent d'une forme rare de dyslexie intergalactique :
> elles auraient confondu **Pasquet** avec **Pasquier**.
>
> Un astronaute a bord consommait environ 98% de ces brioches. Elles servaient donc de
> contrepoids naturel a la station. Depuis leur disparition, l'ISS penche dangereusement
> sur tribord.
>
> **Votre mission :** Reorganiser la salle de stockage pour reequilibrer la station.
> Placez les 4 cargaisons aux distances exactes indiquees par l'ordinateur de bord.

## Comment ca marche

Le joueur place des objets devant un **capteur de proximite infrarouge** (Sharp GP2Y0A21YK0F) relie a une carte **TI TIVA TM4C123G**. Le capteur mesure la distance en temps reel.

4 etapes sequentielles :
| Etape | Objet | Distance cible | Tolerance |
|-------|-------|----------------|-----------|
| 1 | Caisse de rations | 20 cm | +/- 4 cm |
| 2 | Reservoir O2 auxiliaire | 35 cm | +/- 4 cm |
| 3 | Module de communication | 50 cm | +/- 4 cm |
| 4 | Conteneur de brioches vide | 65 cm | +/- 4 cm |

Chaque objet doit rester dans la zone de tolerance pendant **3 secondes** pour valider l'etape. Les 4 etapes validees = enigme reussie, progression 100%.

## Stack technique

- **Frontend** : HTML, CSS, JS (vanilla) -- theme mission control ISS
- **Backend** : PHP natif (PDO, requetes preparees)
- **BDD** : MariaDB partagee (5 equipes, meme base)
- **Hardware** : Carte TIVA TM4C123G + capteur Sharp GP2Y0A21YK0F, liaison serie PHP (extension dio)
- **DataViz** : Chart.js

## Structure du projet

```
web/                         Site web (serveur PHP lance depuis ici)
  css/                       Feuilles de style (theme ISS)
  js/                        Scripts client (polling, graphiques, animations)
  php/
    api/                     Endpoints JSON (dashboard, sensor, history, reset)
    auth/                    Pages login / register / logout
    includes/                Config BDD, auth, header/footer
    pages/                   Dashboard, cargo bay
  index.php                  Point d'entree
serial/
  read_sensor.php            Lecture port serie TIVA + validation du jeu
sql/
  init.sql                   Schema BDD (tables g5e_*)
tiva/
  src/main.cpp               Code capteur (PlatformIO / Energia)
  platformio.ini             Config PlatformIO
```

## Installation

### 1. Cloner le repo

```bash
git clone https://github.com/perazaf1/escape-the-iss.git
cd escape-the-iss
```

### 2. Configurer la base de donnees

```bash
cp web/php/includes/db.config.example.php web/php/includes/db.config.php
```

Editer `db.config.php` et renseigner les identifiants de connexion MariaDB.

Puis executer `sql/init.sql` dans phpMyAdmin ou en CLI pour creer les tables `g5e_*`.

### 3. Extension PHP dio (liaison serie)

L'extension **Direct IO** est necessaire pour la communication avec la carte TIVA.

- **Windows** : telecharger la DLL depuis [pecl.php.net/package/dio](http://pecl.php.net/package/dio), copier dans le dossier `ext/` de PHP, ajouter `extension=php_dio.dll` dans `php.ini`
- **Linux** : `sudo pecl install dio-0.3.0`, ajouter `extension=dio.so` dans `php.ini`

### 4. Lancer le serveur web

```bash
cd web
php -S localhost:8080
```

Ouvrir http://localhost:8080 dans le navigateur.

### 5. Lancer la lecture du capteur

Dans un autre terminal, avec la carte TIVA branchee en USB :

```bash
php serial/read_sensor.php
```

Le script lit le port serie (COM3 par defaut), insere les mesures en BDD, et ecrit `serial/latest.json` pour l'affichage temps reel sur le dashboard.

## Fonctionnalites

- **Authentification** : inscription, connexion, logout, roles joueur / game master
- **Dashboard temps reel** : progression des 5 salles, jauge SVG animee, sparkline, chrono session
- **Cargo Bay** : visualisation schematique des zones cibles, sensor line live, barre de hold, toast de validation
- **Historique Chart.js** : courbes d'evolution capteur, filtrable par periode (5min a 24h)
- **Validation automatique** : le script serie detecte les etapes, met a jour la progression, genere des alertes
- **Reset** : remise a zero complete (alertes, logs, sessions, progression)

## Equipe

Projet realise par 6 etudiants ISEP A1 -- Groupe G5E.

## Licence

Projet academique -- ISEP 2025-2026.
