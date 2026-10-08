# Schéma métier PostgreSQL

Ce document correspond exactement à `database/schema.sql`.

Abréviations :

- `PK` : clé primaire ;
- `FK` : clé étrangère ;
- `UQ` : valeur ou combinaison unique.

## Table `brands`

| Attribut | Contraintes |
|---|---|
| `id` | PK |
| `name` | Texte obligatoire, UQ |
| `created_at` | Date de création obligatoire |
| `updated_at` | Date de modification obligatoire |

## Table `vehicle_models`

| Attribut | Contraintes |
|---|---|
| `id` | PK |
| `brand_id` | FK vers `brands.id`, obligatoire |
| `name` | Texte obligatoire |
| `created_at` | Date de création obligatoire |
| `updated_at` | Date de modification obligatoire |

UQ : `brand_id` + `name`.

## Table `vehicles`

| Groupe | Attributs |
|---|---|
| Clés | `id` (PK), `vehicle_model_id` (FK), `last_sync_run_id` (FK facultative) |
| Annonce | `external_reference` (UQ), `title`, `version`, `source_url` |
| Identification | `vin` (UQ), `registration` (UQ), `vehicle_type` |
| Mise en circulation | `year`, `registration_date`, `mileage`, `is_mileage_guaranteed`, `is_imported`, `is_first_hand` |
| Moteur | `energy`, `gearbox`, `transmission`, `power`, `fiscal_power`, `emission_wltp` |
| Apparence | `body`, `color`, `doors`, `seats`, `warranty` |
| Électrique | `electric_range_wltp`, `battery_capacity` |
| Prix | `price`, `merchant_price`, `partner_price`, `catalog_price`, `vat_reclaimable`, `tax_code` |
| API | `availability_date`, `source_inserted_at`, `source_updated_at`, `source_deleted_at`, `last_synced_at`, `is_active`, `raw_payload` |
| Laravel | `created_at`, `updated_at` |

Règles principales :

- `external_reference` identifie l'annonce Glinche et empêche les doublons ;
- les kilométrages, puissances, émissions et prix ne peuvent pas être négatifs ;
- `raw_payload` conserve en JSONB les champs de l'API qui ne sont pas utilisés directement par l'interface ;
- `is_active` permet de masquer un véhicule retiré de l'API sans effacer son historique ;
- les prix restent dans `vehicles` car l'application utilise uniquement le prix courant.

## Table `vehicle_pictures`

| Attribut | Contraintes |
|---|---|
| `id` | PK |
| `vehicle_id` | FK vers `vehicles.id`, obligatoire |
| `type` | Texte obligatoire, par exemple `MAIN` |
| `url` | URL obligatoire |
| `position` | Entier positif, ordre d'affichage |
| `created_at` | Date de création obligatoire |
| `updated_at` | Date de modification obligatoire |

Règles :

- UQ : `vehicle_id` + `url` ;
- un véhicule possède au maximum une photo de type `MAIN` ;
- supprimer un véhicule supprime automatiquement ses photos.

## Table `sync_runs`

| Attribut | Contraintes |
|---|---|
| `id` | PK |
| `status` | `RUNNING`, `SUCCESS`, `PARTIAL` ou `FAILED` |
| `started_at` | Date de début obligatoire |
| `finished_at` | Date de fin facultative |
| `vehicles_received` | Compteur positif |
| `vehicles_created` | Compteur positif |
| `vehicles_updated` | Compteur positif |
| `vehicles_deactivated` | Compteur positif |
| `error_message` | Message facultatif |

Cette table permet de diagnostiquer les imports de l'API sans enregistrer un journal excessivement détaillé.

## Relations et cardinalités

Les relations utilisent des verbes à l'infinitif.

```text
BRAND         (0,N) ───── POSSÉDER ───── (1,1) VEHICLE_MODEL

VEHICLE_MODEL (0,N) ─── CARACTÉRISER ─── (1,1) VEHICLE

VEHICLE       (0,N) ───── ILLUSTRER ───── (1,1) VEHICLE_PICTURE

SYNC_RUN      (0,N) ───── TRAITER ─────── (0,1) VEHICLE
```

- Une marque peut posséder zéro à plusieurs modèles ; un modèle appartient à une seule marque.
- Un modèle peut caractériser zéro à plusieurs véhicules ; un véhicule correspond à un seul modèle.
- Un véhicule peut être illustré par zéro à plusieurs photos ; une photo illustre un seul véhicule.
- Une synchronisation peut traiter zéro à plusieurs véhicules ; un véhicule peut référencer sa dernière synchronisation ou aucune.

## Choix de simplification

Les équipements, options, dimensions très détaillées, documents administratifs et informations électriques secondaires ne possèdent pas de tables dédiées. Ils restent disponibles dans `vehicles.raw_payload`.

Ce choix garde une base relationnelle claire pour les besoins réels : afficher les véhicules, filtrer par marque, montrer leur prix et leurs photos, puis les actualiser depuis l'API.

Les tables Laravel `users`, `cache` et `jobs` ne figurent pas ici : elles sont déjà gérées par les migrations fournies avec Laravel.
