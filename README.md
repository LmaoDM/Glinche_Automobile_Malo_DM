# Glinche Automobiles - catalogue de véhicules

J’ai réalisé cette application dans le cadre d’un exercice technique full-stack. Le but est de récupérer les véhicules fournis par l’API partenaire Glinche Automobiles, de les enregistrer dans PostgreSQL puis de les afficher dans un catalogue responsive.

## Fonctionnalités

- authentification auprès de l’API partenaire Glinche ;
- synchronisation des véhicules dans PostgreSQL ;
- mise à jour des véhicules déjà présents ;
- désactivation des véhicules disparus du catalogue partenaire ;
- conservation des photos et des principales caractéristiques ;
- affichage responsive du catalogue ;
- filtrage dynamique par marque ;
- thèmes clair et sombre ;
- gestion des états de chargement, d’erreur et de résultat vide ;
- API interne Laravel pour les véhicules et les marques.

## Technologies

- PHP 8.2 et Laravel 12 ;
- Vue.js 3 et Vite ;
- Bootstrap 5 et Bootstrap Icons ;
- PostgreSQL ;
- PHPUnit.

## Prérequis

- PHP 8.2 ou supérieur avec les extensions `pdo_pgsql`, `pgsql`, `intl` et `pdo_sqlite` ;
- Composer ;
- Node.js et npm ;
- PostgreSQL.

## Installation

Cloner le dépôt puis installer les dépendances :

```bash
git clone https://github.com/LmaoDM/Glinche_Automobile_Malo_DM.git
cd Glinche_Automobile_Malo_DM
composer install
npm install
```

Créer le fichier d’environnement et générer la clé Laravel :

```bash
cp .env.example .env
php artisan key:generate
```

Sous Windows PowerShell, la copie peut être effectuée avec :

```powershell
Copy-Item .env.example .env
```

Créer une base PostgreSQL nommée `glinche_automobile`, puis renseigner dans `.env` les paramètres de connexion et les identifiants transmis pour l’API partenaire :

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=glinche_automobile
DB_USERNAME=postgres
DB_PASSWORD=

GLINCHE_API_BASE_URL=https://marketplace-dev.glinche-automobiles.com/api
GLINCHE_API_EMAIL=
GLINCHE_API_PASSWORD=
```

Les identifiants de l’API ne doivent jamais être ajoutés au dépôt Git.

Créer les tables avec les migrations Laravel :

```bash
php artisan migrate
```

Le fichier [`database/schema.sql`](database/schema.sql) décrit également le schéma PostgreSQL. Il sert de référence et ne doit pas être exécuté après les migrations, au risque de tenter de recréer les mêmes tables.

## Synchronisation du catalogue

Importer ou actualiser les véhicules avec :

```bash
php artisan vehicles:sync
```

La synchronisation :

1. s’authentifie auprès de l’API Glinche ;
2. récupère les annonces disponibles ;
3. crée ou actualise les marques, modèles, véhicules et photos ;
4. désactive les véhicules qui ne sont plus retournés par l’API ;
5. enregistre le résultat dans la table `sync_runs` ;
6. ferme la session auprès de l’API partenaire.

La commande peut être relancée sans créer de doublons : la référence externe du véhicule sert d’identifiant unique de synchronisation.

## Lancement en développement

Lancer Laravel dans un premier terminal :

```bash
php artisan serve
```

Lancer Vite dans un second terminal :

```bash
npm run dev
```

L’application est ensuite disponible à l’adresse [http://127.0.0.1:8000](http://127.0.0.1:8000).

Pour produire les fichiers optimisés :

```bash
npm run build
```

## API interne

| Méthode | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/vehicles` | Retourne les véhicules actifs |
| `GET` | `/api/vehicles?brand=Renault` | Filtre les véhicules par marque |
| `GET` | `/api/brands` | Retourne les marques possédant au moins un véhicule actif |

## Tests et qualité du code

Exécuter les tests :

```bash
php artisan test
```

Les tests utilisent SQLite en mémoire et ne modifient donc pas la base PostgreSQL locale. Ils couvrent notamment :

- le catalogue et son filtre par marque ;
- l’authentification simulée auprès de l’API partenaire ;
- l’import des véhicules et de leurs photos ;
- la mise à jour des véhicules existants ;
- la désactivation des véhicules absents d’une synchronisation ;
- l’enregistrement d’une synchronisation échouée.

Vérifier le formatage PHP :

```bash
vendor/bin/pint --test
```

Appliquer automatiquement le formatage :

```bash
vendor/bin/pint
```

## Structure principale

```text
app/
├── Console/Commands/SyncVehicles.php
├── Http/Controllers/Api/
├── Http/Resources/VehicleResource.php
├── Models/
└── Services/
    ├── GlincheApiClient.php
    └── VehicleSynchronizer.php

resources/
├── css/app.css
└── js/App.vue

database/
├── migrations/
└── schema.sql

tests/Feature/
├── Api/VehicleCatalogTest.php
└── Services/VehicleSynchronizerTest.php
```

## Mes choix techniques

J’ai choisi d’enregistrer les véhicules dans PostgreSQL plutôt que d’appeler directement l’API à chaque chargement de la page. Le catalogue reste ainsi accessible à partir des dernières données importées, même si l’API partenaire rencontre temporairement un problème.

J’ai séparé les appels à l’API dans `GlincheApiClient` et l’enregistrement des données dans `VehicleSynchronizer`. Cela m’a aussi permis de tester la synchronisation avec de fausses réponses HTTP sans utiliser les vrais identifiants pendant les tests.

L’import est effectué dans une transaction. Si une erreur se produit pendant l’enregistrement, les modifications en cours sont annulées pour éviter de conserver un catalogue incomplet.

La synchronisation des véhicules se lance manuellement avec la commande `php artisan vehicles:sync`. Avec plus de temps, j’aurais pu l’automatiser à intervalles réguliers avec le planificateur de tâches de Laravel.

## Avec plus de temps

J’aurais notamment ajouté une recherche par modèle, davantage de filtres et une page de détail pour chaque véhicule. J’aurais également complété les tests du frontend Vue et automatisé la synchronisation du catalogue.
