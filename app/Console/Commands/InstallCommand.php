<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * First-run installer.
 *
 * Creates the first administrator interactively. No default password is ever
 * written: the operator must type one, which satisfies the spec requirement
 * that a production database contains no known credentials.
 */
class InstallCommand extends Command
{
    protected $signature = 'erp:install
                            {--force : Re-run even if an administrator already exists}';

    protected $description = 'Set up the ERP: seed roles/permissions and create the first administrator.';

    public function handle(): int
    {
        $this->info('Petrol Pump ERP — installer');
        $this->line('');

        if (! $this->option('force') && User::query()->whereHas('roles', fn ($q) => $q->where('is_super_admin', true))->exists()) {
            $this->error('An administrator already exists. Use --force to add another.');
            $this->line('To reset a forgotten password, use: php artisan erp:install --force');

            return self::FAILURE;
        }

        // 1. Reference data.
        $this->components->task('Seeding roles and permissions', function () {
            $this->callSilent('db:seed', ['--class' => 'RolePermissionSeeder', '--force' => true]);

            return true;
        });

        $permissionCount = DB::table('permissions')->count();
        $roleCount = DB::table('roles')->count();
        $this->line("   <info>{$permissionCount}</info> permissions across <info>{$roleCount}</info> roles.");
        $this->line('');

        // 2. Company name.
        $companyName = (string) $this->ask('Company name', config('app.name'));
        if ($companyName === '') {
            $companyName = config('app.name');
        }

        config(['app.name' => $companyName]);
        $this->storeCompanyName($companyName);

        // 3. First branch.
        $branch = $this->createFirstBranch();
        $this->line('');

        // 4. Administrator.
        $admin = $this->createAdministrator($branch);

        $this->line('');
        $this->info('Installation complete.');
        $this->line('   Company:  '.$companyName);
        $this->line('   Admin:    '.$admin->email);
        $this->line('   Branch:   '.($branch->name ?? 'none (create one from the Branches screen)'));
        $this->line('');
        $this->line('Start the application with: php artisan serve');

        return self::SUCCESS;
    }

    private function storeCompanyName(string $name): void
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            return;
        }

        $contents = (string) file_get_contents($path);

        if (preg_match('/^APP_NAME=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME="'.addslashes($name).'"', $contents, 1);
        } else {
            $contents = 'APP_NAME="'.addslashes($name).'"'.PHP_EOL.$contents;
        }

        file_put_contents($path, $contents);
    }

    private function createFirstBranch(): ?Branch
    {
        if (Branch::query()->exists()) {
            $this->components->info('Branches already exist — skipping.');

            return null;
        }

        $name = (string) $this->ask('First branch name', 'Main Branch');
        $code = strtoupper((string) $this->ask('First branch code', 'BR-01'));

        try {
            $branch = Branch::create([
                'code' => $code,
                'name' => $name !== '' ? $name : 'Main Branch',
                'status' => Branch::STATUS_ACTIVE,
            ]);

            $this->components->info("Branch '{$branch->name}' created.");

            return $branch;
        } catch (Throwable $e) {
            $this->warn('Could not create the branch: '.$e->getMessage());
            $this->line('   You can create it from the Branches screen after signing in.');

            return null;
        }
    }

    private function createAdministrator(?Branch $branch): User
    {
        $name = trim((string) $this->ask('Administrator name', 'Administrator'));
        $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Administrator password (min 8 characters)');
        $confirm = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:150|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error('   '.$error);
            }

            $this->line('');
            $this->error('Administrator not created. Re-run: php artisan erp:install');

            throw new \RuntimeException('Invalid administrator details supplied to erp:install.');
        }

        $admin = DB::transaction(function () use ($name, $email, $password, $branch) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                // Hash explicitly: the model casts to 'hashed', but being
                // explicit here guarantees it regardless of cast config.
                'password' => Hash::make($password),
                'status' => User::STATUS_ACTIVE,
                'password_changed_at' => now(),
            ]);

            $user->roles()->sync(
                Role::query()->where('name', Role::ADMIN)->pluck('id')->all()
            );

            if ($branch) {
                $user->branches()->sync([$branch->id => ['is_default' => true]]);
            }

            return $user;
        });

        $this->components->info("Administrator '{$admin->email}' created.");

        return $admin;
    }
}
