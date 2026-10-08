BEGIN;

CREATE TABLE brands (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehicle_models (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    brand_id BIGINT NOT NULL REFERENCES brands(id) ON DELETE RESTRICT,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT vehicle_models_brand_name_unique UNIQUE (brand_id, name)
);

CREATE TABLE sync_runs (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    status VARCHAR(20) NOT NULL DEFAULT 'RUNNING',
    started_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMPTZ,
    vehicles_received INTEGER NOT NULL DEFAULT 0,
    vehicles_created INTEGER NOT NULL DEFAULT 0,
    vehicles_updated INTEGER NOT NULL DEFAULT 0,
    vehicles_deactivated INTEGER NOT NULL DEFAULT 0,
    error_message TEXT,
    CONSTRAINT sync_runs_status_check
        CHECK (status IN ('RUNNING', 'SUCCESS', 'PARTIAL', 'FAILED')),
    CONSTRAINT sync_runs_counters_check
        CHECK (
            vehicles_received >= 0
            AND vehicles_created >= 0
            AND vehicles_updated >= 0
            AND vehicles_deactivated >= 0
        ),
    CONSTRAINT sync_runs_dates_check
        CHECK (finished_at IS NULL OR finished_at >= started_at)
);

CREATE TABLE vehicles (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    vehicle_model_id BIGINT NOT NULL REFERENCES vehicle_models(id) ON DELETE RESTRICT,
    last_sync_run_id BIGINT REFERENCES sync_runs(id) ON DELETE SET NULL,

    external_reference VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    version VARCHAR(255),
    source_url TEXT,

    vin VARCHAR(17) UNIQUE,
    registration VARCHAR(20) UNIQUE,
    vehicle_type VARCHAR(30),
    year SMALLINT,
    registration_date DATE,
    mileage INTEGER NOT NULL DEFAULT 0,
    is_mileage_guaranteed BOOLEAN,
    is_imported BOOLEAN,
    is_first_hand BOOLEAN,

    energy VARCHAR(20) NOT NULL,
    gearbox VARCHAR(20),
    transmission VARCHAR(100),
    power INTEGER,
    fiscal_power INTEGER,
    emission_wltp INTEGER,

    body VARCHAR(50),
    color VARCHAR(100),
    doors SMALLINT,
    seats SMALLINT,
    warranty TEXT,

    electric_range_wltp NUMERIC(8, 2),
    battery_capacity NUMERIC(8, 2),

    price NUMERIC(12, 2),
    merchant_price NUMERIC(12, 2),
    partner_price NUMERIC(12, 2),
    catalog_price NUMERIC(12, 2),
    vat_reclaimable BOOLEAN,
    tax_code VARCHAR(20),

    availability_date TIMESTAMPTZ,
    source_inserted_at TIMESTAMPTZ,
    source_updated_at TIMESTAMPTZ,
    source_deleted_at TIMESTAMPTZ,
    last_synced_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    raw_payload JSONB NOT NULL DEFAULT '{}'::JSONB,

    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT vehicles_year_check CHECK (year IS NULL OR year BETWEEN 1886 AND 2100),
    CONSTRAINT vehicles_mileage_check CHECK (mileage >= 0),
    CONSTRAINT vehicles_power_check CHECK (power IS NULL OR power >= 0),
    CONSTRAINT vehicles_fiscal_power_check CHECK (fiscal_power IS NULL OR fiscal_power >= 0),
    CONSTRAINT vehicles_emission_check CHECK (emission_wltp IS NULL OR emission_wltp >= 0),
    CONSTRAINT vehicles_doors_check CHECK (doors IS NULL OR doors > 0),
    CONSTRAINT vehicles_seats_check CHECK (seats IS NULL OR seats > 0),
    CONSTRAINT vehicles_electric_range_check CHECK (electric_range_wltp IS NULL OR electric_range_wltp >= 0),
    CONSTRAINT vehicles_battery_capacity_check CHECK (battery_capacity IS NULL OR battery_capacity >= 0),
    CONSTRAINT vehicles_prices_check CHECK (
        (price IS NULL OR price >= 0)
        AND (merchant_price IS NULL OR merchant_price >= 0)
        AND (partner_price IS NULL OR partner_price >= 0)
        AND (catalog_price IS NULL OR catalog_price >= 0)
    )
);

CREATE TABLE vehicle_pictures (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    vehicle_id BIGINT NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL DEFAULT 'OTHER',
    url TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT vehicle_pictures_position_check CHECK (position >= 0),
    CONSTRAINT vehicle_pictures_vehicle_url_unique UNIQUE (vehicle_id, url)
);

CREATE INDEX vehicle_models_brand_id_index ON vehicle_models (brand_id);
CREATE INDEX vehicles_model_id_index ON vehicles (vehicle_model_id);
CREATE INDEX vehicles_active_index ON vehicles (is_active);
CREATE INDEX vehicles_active_model_index ON vehicles (is_active, vehicle_model_id);
CREATE INDEX vehicles_source_updated_at_index ON vehicles (source_updated_at);
CREATE INDEX vehicles_last_sync_run_id_index ON vehicles (last_sync_run_id);
CREATE INDEX vehicle_pictures_vehicle_position_index ON vehicle_pictures (vehicle_id, position);
CREATE UNIQUE INDEX vehicle_pictures_one_main_per_vehicle
    ON vehicle_pictures (vehicle_id)
    WHERE type = 'MAIN';

CREATE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER brands_set_updated_at
    BEFORE UPDATE ON brands
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER vehicle_models_set_updated_at
    BEFORE UPDATE ON vehicle_models
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER vehicles_set_updated_at
    BEFORE UPDATE ON vehicles
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER vehicle_pictures_set_updated_at
    BEFORE UPDATE ON vehicle_pictures
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

COMMIT;
