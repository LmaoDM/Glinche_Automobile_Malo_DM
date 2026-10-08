<?php

namespace App\Console\Commands;

use App\Services\VehicleSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class SyncVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les véhicules depuis l’API partenaire Glinche';

    /**
     * Execute the console command.
     */
    public function handle(VehicleSynchronizer $synchronizer): int
    {
        $this->info('Synchronisation des véhicules en cours…');

        try {
            $result = $synchronizer->synchronize();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Reçus', 'Créés', 'Mis à jour', 'Désactivés'],
            [[
                $result['received'],
                $result['created'],
                $result['updated'],
                $result['deactivated'],
            ]],
        );

        $this->info('Synchronisation terminée.');

        return self::SUCCESS;
    }
}
