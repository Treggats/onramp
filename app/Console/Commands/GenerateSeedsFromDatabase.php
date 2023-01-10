<?php

namespace App\Console\Commands;

use App\Concerns\CanGenerateFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use STS\Tunneler\Jobs\CreateTunnel;
use Illuminate\Support\Facades\File;

class GenerateSeedsFromDatabase extends Command
{
    use CanGenerateFile;

    protected const SEED_FILE_EXT = 'json';

    protected $signature = 'generate:seeds-from-db {--override} {--all}';

    protected $description = 'Sync core data and generate new seed files for local development.';

    public function __construct()
    {
        parent::__construct();

        dispatch(new CreateTunnel());

        $this->dirPath = config('seeder.directory');
    }

    public function handle()
    {
        $methods = collect([
            'Modules' => 'syncModules',
            'Resources' => 'syncResources',
            'Skills' => 'syncSkills',
            'Terms' => 'syncTerms',
            'Tracks' => 'syncTracks',
            'All' => 'syncAll',
        ]);

        if ($this->option('all')) {
            $this->syncAll();
            return 0;
        }

        $option = $this->choice('Which seeder data would you like to sync?', $methods->keys()->toArray());

        $this->{$methods[$option]}();

        // call artisan command to update seeder and run
    }

    private function syncAll()
    {
        // sync content
        $this->line('Preparing to overwrite all seeder files ...');

        $this->syncData('modules', true);
        $this->syncData('resources', true);
        $this->syncData('skills', true);
        $this->syncData('terms', true);
        $this->syncData('tracks', true);

        // sync relationships
        $this->line('Syncing relationships ...');

        $this->syncData('module_resource', true);
        $this->syncData('module_track', true);
        $this->syncData('resource_term', true);
        $this->syncData('term_term', true);

        $this->info('Done!');
    }

    private function syncTerms()
    {
        if ($this->syncData('terms')) {
            $this->syncData('resource_term', true);
            $this->syncData('term_term', true);
        }
    }

    private function syncModules()
    {
        if ($this->syncData('modules')) {
            $this->syncData('module_resource', true);
            $this->syncData('module_track', true);
        }
    }

    private function syncResources()
    {
        if ($this->syncData('resources')) {
            $this->syncData('module_resource', true);
            $this->syncData('resource_term', true);
        }
    }

    private function syncSkills()
    {
        $this->syncData('skills');
    }

    private function syncTracks()
    {
        if ($this->syncData('tracks')) {
            $this->syncData('module_track', true);
        }
    }

    private function syncData(string $table, bool $override = false)
    {
        $override = $this->option('override') ?: $override;

        $ext = config('seeder.extension', self::SEED_FILE_EXT);
        $seederName = $table . '.' . $ext;

        $this->line("Getting $table from " . config('database.connections.mysql_tunnel.database'));

        $this->line("Generating $seederName");

        $path = $this->createFile($table, $ext);

        if (File::exists($path)) {
            $continue = $override ?: $this->confirm("The seeder source file $seederName already exists. Are you sure you want to override its contents?");

            if (! $continue) {
                $this->info('Goodbye!');
                return 0;
            }
        }

        $data = DB::connection('mysql_tunnel')
            ->table($table)
            ->orderBy('id')
            ->chunkMap(function ($item) {
                return $item;
            });

        File::put($path, json_encode($data->toArray()));

        $this->info("$table synced" . PHP_EOL);

        return 1;
    }
}
