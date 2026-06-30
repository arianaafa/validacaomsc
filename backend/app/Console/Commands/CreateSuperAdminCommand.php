<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class CreateSuperAdminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'aura:create-superadmin';

    /**
     * @var string
     */
    protected $description = 'Cria um usuário SuperAdmin global da plataforma';

    public function handle(): int
    {
        $passwordFromEnv = filled(env('AURA_SUPERADMIN_PASSWORD'));

        $name = $this->resolveName();
        $email = $this->resolveEmail();
        $password = $this->resolvePassword($passwordFromEnv);
        $passwordConfirmation = $passwordFromEnv
            ? $password
            : $this->secret('Confirme a senha');

        $passwordRules = ['required', 'string', Password::min(8)];

        if (! $passwordFromEnv) {
            $passwordRules[] = 'confirmed';
        }

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => $passwordRules,
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'municipality_id' => null,
            'is_superadmin' => true,
            'force_password_change' => false,
        ]);

        $this->info("SuperAdmin \"{$validated['name']}\" ({$validated['email']}) criado com sucesso.");

        return self::SUCCESS;
    }

    private function resolveName(): string
    {
        $name = env('AURA_SUPERADMIN_NAME');

        if (filled($name)) {
            return (string) $name;
        }

        return (string) $this->ask('Nome');
    }

    private function resolveEmail(): string
    {
        $email = env('AURA_SUPERADMIN_EMAIL');

        if (filled($email)) {
            return (string) $email;
        }

        return (string) $this->ask('E-mail');
    }

    private function resolvePassword(bool $passwordFromEnv): string
    {
        if ($passwordFromEnv) {
            return (string) env('AURA_SUPERADMIN_PASSWORD');
        }

        return (string) $this->secret('Senha');
    }
}
