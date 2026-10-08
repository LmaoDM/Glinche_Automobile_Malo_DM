<script setup>
import { computed, onMounted, ref } from 'vue';

const brands = ref([]);
const vehicles = ref([]);
const selectedBrand = ref('');
const loading = ref(true);
const error = ref('');
const theme = ref(localStorage.getItem('theme') ?? 'light');

const filteredVehicles = computed(() => {
    if (!selectedBrand.value) {
        return vehicles.value;
    }

    return vehicles.value.filter((vehicle) => vehicle.brand === selectedBrand.value);
});

function applyTheme(value) {
    theme.value = value;
    document.documentElement.setAttribute('data-bs-theme', value);
    localStorage.setItem('theme', value);
}

function toggleTheme() {
    applyTheme(theme.value === 'light' ? 'dark' : 'light');
}

function formatPrice(value) {
    if (value === null || value === undefined) {
        return 'Prix sur demande';
    }

    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatMileage(value) {
    return `${new Intl.NumberFormat('fr-FR').format(value)} km`;
}

function handleImageError(event) {
    event.target.classList.add('d-none');
    event.target.nextElementSibling?.classList.remove('d-none');
}

async function loadCatalog() {
    loading.value = true;
    error.value = '';

    try {
        const [vehiclesResponse, brandsResponse] = await Promise.all([
            fetch('/api/vehicles', { headers: { Accept: 'application/json' } }),
            fetch('/api/brands', { headers: { Accept: 'application/json' } }),
        ]);

        if (!vehiclesResponse.ok || !brandsResponse.ok) {
            throw new Error('Le catalogue est momentanément indisponible.');
        }

        const [vehiclesPayload, brandsPayload] = await Promise.all([
            vehiclesResponse.json(),
            brandsResponse.json(),
        ]);

        vehicles.value = vehiclesPayload.data ?? [];
        brands.value = brandsPayload.data ?? [];
    } catch (exception) {
        error.value = exception.message || 'Une erreur inattendue est survenue.';
    } finally {
        loading.value = false;
    }
}

applyTheme(theme.value);
onMounted(loadCatalog);
</script>

<template>
    <div class="app-shell">
        <header class="site-header sticky-top">
            <nav class="container d-flex align-items-center justify-content-between py-3" aria-label="Navigation principale">
                <a class="brand-lockup text-decoration-none" href="/" aria-label="Accueil Glinche Automobiles">
                    <span class="brand-logo-frame">
                        <img class="brand-logo" :src="'/images/glinche-logo.png'" alt="Glinche Automobiles">
                    </span>
                </a>

                <button class="theme-toggle" type="button" :aria-label="theme === 'light' ? 'Activer le thème sombre' : 'Activer le thème clair'" @click="toggleTheme">
                    <i :class="theme === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill'" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">{{ theme === 'light' ? 'Sombre' : 'Clair' }}</span>
                </button>
            </nav>
        </header>

        <main>
            <section class="hero-section">
                <div class="container py-5 py-lg-6">
                    <div class="row align-items-end g-4">
                        <div class="col-lg-8">
                            <span class="eyebrow">Notre sélection du moment</span>
                            <h1 class="display-4 fw-bold mt-3 mb-3">Trouvez le véhicule de vos rêves en quelques clics !</h1>
                            <p class="hero-copy mb-0">Découvrez nos véhicules disponibles, sélectionnés et actualisés depuis notre catalogue partenaire.</p>
                        </div>
                        <div class="col-lg-4">
                            <div class="inventory-summary ms-lg-auto">
                                <span class="inventory-number">{{ vehicles.length }}</span>
                                <span>véhicule{{ vehicles.length > 1 ? 's' : '' }} disponible{{ vehicles.length > 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="catalog-section py-4 py-lg-5">
                <div class="container">
                    <div class="filter-panel mb-4 mb-lg-5">
                        <div>
                            <span class="filter-label">Filtrer le catalogue</span>
                            <h2 class="h4 mb-0 mt-1">Choisissez une marque</h2>
                        </div>

                        <div class="brand-filter">
                            <label class="visually-hidden" for="brand">Marque</label>
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                            <select id="brand" v-model="selectedBrand" class="form-select" :disabled="loading">
                                <option value="">Toutes les marques</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.name">
                                    {{ brand.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="loading" class="state-panel" aria-live="polite">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement</span></div>
                        <h2 class="h5 mt-3">Chargement du catalogue…</h2>
                        <p class="mb-0 text-body-secondary">Nous préparons les véhicules disponibles.</p>
                    </div>

                    <div v-else-if="error" class="state-panel" role="alert">
                        <i class="bi bi-exclamation-triangle state-icon text-warning" aria-hidden="true"></i>
                        <h2 class="h5 mt-3">Impossible de charger les véhicules</h2>
                        <p class="text-body-secondary">{{ error }}</p>
                        <button class="btn btn-primary" type="button" @click="loadCatalog">Réessayer</button>
                    </div>

                    <template v-else>
                        <div class="catalog-heading d-flex align-items-center justify-content-between mb-3">
                            <p class="mb-0 text-body-secondary" aria-live="polite">
                                <strong class="text-body">{{ filteredVehicles.length }}</strong>
                                résultat{{ filteredVehicles.length > 1 ? 's' : '' }}
                                <span v-if="selectedBrand"> pour {{ selectedBrand }}</span>
                            </p>
                            <button v-if="selectedBrand" class="btn btn-link btn-sm text-decoration-none" type="button" @click="selectedBrand = ''">
                                Effacer le filtre
                            </button>
                        </div>

                        <div v-if="filteredVehicles.length" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                            <div v-for="vehicle in filteredVehicles" :key="vehicle.id" class="col">
                                <article class="vehicle-card card h-100 border-0">
                                    <div class="vehicle-media">
                                        <img v-if="vehicle.picture" :src="vehicle.picture" :alt="`${vehicle.brand} ${vehicle.model}`" loading="lazy" @error="handleImageError">
                                        <div class="vehicle-placeholder" :class="{ 'd-none': vehicle.picture }">
                                            <i class="bi bi-car-front" aria-hidden="true"></i>
                                            <span>Photo indisponible</span>
                                        </div>
                                        <span v-if="vehicle.year" class="year-badge">{{ vehicle.year }}</span>
                                    </div>

                                    <div class="card-body d-flex flex-column p-4">
                                        <p class="vehicle-brand mb-2">{{ vehicle.brand }}</p>
                                        <h2 class="h4 card-title mb-1">{{ vehicle.model }}</h2>
                                        <p class="vehicle-version text-body-secondary mb-4">{{ vehicle.version || 'Version non renseignée' }}</p>

                                        <div class="vehicle-specs mb-4">
                                            <span><i class="bi bi-speedometer2" aria-hidden="true"></i>{{ formatMileage(vehicle.mileage) }}</span>
                                            <span><i class="bi bi-fuel-pump" aria-hidden="true"></i>{{ vehicle.energy.label }}</span>
                                            <span v-if="vehicle.gearbox"><i class="bi bi-gear" aria-hidden="true"></i>{{ vehicle.gearbox }}</span>
                                        </div>

                                        <div class="mt-auto">
                                            <div>
                                                <small class="price-label">Prix TTC</small>
                                                <p class="vehicle-price mb-0">{{ formatPrice(vehicle.price) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div v-else class="state-panel">
                            <i class="bi bi-search state-icon" aria-hidden="true"></i>
                            <h2 class="h5 mt-3">Aucun véhicule trouvé</h2>
                            <p class="text-body-secondary">Aucun véhicule ne correspond à cette marque.</p>
                            <button class="btn btn-primary" type="button" @click="selectedBrand = ''">Voir tous les véhicules</button>
                        </div>
                    </template>
                </div>
            </section>
        </main>

        <footer class="site-footer py-4">
            <div class="container d-flex flex-column flex-sm-row gap-2 justify-content-between">
                <span>Glinche Automobiles</span>
                <span>Catalogue actualisé depuis l’API partenaire</span>
            </div>
        </footer>
    </div>
</template>
