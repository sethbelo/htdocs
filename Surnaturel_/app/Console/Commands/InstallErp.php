<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

#[Signature('erp:install {--email= : E-mail du Master administrateur} {--username=admin : Nom d’utilisateur}')]
#[Description('Initialise les référentiels et crée le compte Master / Super Administrateur')]
class InstallErp extends Command
{
    public function handle(): int
    {
        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => ChartOfAccountsSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => ReferenceDataSeeder::class, '--force' => true]);

        $email = $this->option('email') ?: text('E-mail du Master administrateur', required: true);
        $password = Str::password(14, symbols: false);

        $user = User::query()->updateOrCreate(['username' => $this->option('username')], [
            'name' => 'Master Administrateur',
            'email' => $email,
            'password' => $password,
            'must_change_password' => true,
            'default_site_id' => Site::query()->value('id'),
        ]);
        $user->syncRoles(['super-admin']);
        $user->sites()->sync(Site::query()->pluck('id'));

        $this->components->info("Compte « {$user->username} » prêt. Mot de passe provisoire (à changer à la première connexion) : {$password}");

        return self::SUCCESS;
    }
}
