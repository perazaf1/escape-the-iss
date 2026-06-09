# Escape the ISS — Salle de Stockage (G5E)

Projet de fin d'année ISEP A1 — Escape Game Connecté sur le thème de la Station Spatiale Internationale.

## Le scénario

> **/// ALERTE GÉNÉRALE — PRIORITÉ ABSOLUE ///**
>
> Une bande d'aliens a infiltré l'ISS et a dérobé l'intégralité du stock de brioches.
> D'après nos analystes, ces créatures souffrent d'une forme rare de dyslexie intergalactique :
> elles auraient confondu **Pasquet** avec **Pasquier**.
>
> Un astronaute à bord consommait environ 98% de ces brioches. Elles servaient donc de
> contrepoids naturel à la station. Depuis leur disparition, l'ISS penche dangereusement
> sur tribord.
>
> **Votre mission :** Réorganiser la salle de stockage pour rééquilibrer la station.
> Placez les 4 cargaisons aux distances exactes indiquées par l'ordinateur de bord.

## Comment ça marche

Le joueur place des objets devant un **capteur de proximité infrarouge** (Sharp GP2Y0A21YK0F) relié à une carte **TI TIVA TM4C123G**. Le capteur mesure la distance en temps réel.

3 étapes séquentielles (ordre non croissant volontaire) :
| Étape | Objet | Distance cible | Tolérance |
|-------|-------|----------------|-----------|
| 1 | Réservoir O2 auxiliaire | 35 cm | +/- 4 cm |
| 2 | Caisse de rations | 20 cm | +/- 4 cm |
| 3 | Module de communication | 50 cm | +/- 4 cm |

Chaque objet doit rester dans la zone de tolérance pendant **3 secondes** pour valider l'étape. Les 3 étapes validées = énigme réussie, progression 100%.

## Stack technique

- **Frontend** : HTML, CSS, JS (vanilla) — thème mission control ISS
- **Backend** : PHP natif (PDO, requêtes préparées)
- **BDD** : MariaDB partagée (5 équipes, même base)
- **Hardware** : Carte TIVA TM4C123G + capteur Sharp GP2Y0A21YK0F, liaison série PHP (extension dio)
- **DataViz** : Chart.js

## Structure du projet

```
web/                         Site web (serveur PHP lancé depuis ici)
  css/                       Feuilles de style (thème ISS)
  js/                        Scripts client (polling, graphiques, animations)
  php/
    api/                     Endpoints JSON (dashboard, sensor, history, reset)
    auth/                    Pages login / register / logout
    includes/                Config BDD, auth, header/footer
    pages/                   Dashboard, cargo bay
  index.php                  Point d'entrée
serial/
  read_sensor.php            Lecture port série TIVA + validation du jeu
sql/
  init.sql                   Schéma BDD (tables g5e_*)
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

### 2. Configurer la base de données

```bash
cp web/php/includes/db.config.example.php web/php/includes/db.config.php
```

Éditer `db.config.php` et renseigner les identifiants de connexion MariaDB.

Puis exécuter `sql/init.sql` dans phpMyAdmin ou en CLI pour créer les tables `g5e_*`.

### 3. Extension PHP dio (liaison série)

L'extension **Direct IO** est nécessaire pour la communication avec la carte TIVA.

- **Windows** : télécharger la DLL depuis [pecl.php.net/package/dio](http://pecl.php.net/package/dio), copier dans le dossier `ext/` de PHP, ajouter `extension=php_dio.dll` dans `php.ini`
- **Linux** : `sudo pecl install dio-0.3.0`, ajouter `extension=dio.so` dans `php.ini`

### 4. Lancer le serveur web

```bash
cd web
php -S localhost:8080
```

Ouvrir http://localhost:8080 dans le navigateur.

### 5. Lancer la lecture du capteur

Dans un autre terminal, avec la carte TIVA branchée en USB :

```bash
php serial/read_sensor.php
```

Le script lit le port série (COM3 par défaut), insère les mesures en BDD, et écrit `serial/latest.json` pour l'affichage temps réel sur le dashboard.

## Fonctionnalités

- **Authentification** : inscription, connexion, logout, rôles joueur / game master
- **Dashboard temps réel** : progression des 5 salles, jauge SVG animée, sparkline, chrono session
- **Cargo Bay** : visualisation schématique des zones cibles, sensor line live, barre de hold, toast de validation
- **Historique Chart.js** : courbes d'évolution capteur, filtrable par période (5min à 24h)
- **Validation automatique** : le script série détecte les étapes avec **lissage du capteur** (moyenne glissante) et **tolérance au bruit** (2 lectures hors zone tolérées), met à jour la progression, génère des alertes. Resync automatique après un reset web.
- **Reset** : remise à zéro complète (alertes, logs, sessions, progression) — le script série se resynchronise automatiquement

## Équipe

Projet réalisé par 6 étudiants ISEP A1 — Groupe G5E.

## Licence

Projet académique — ISEP 2025-2026.
