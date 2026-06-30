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
        $name = $this->ask('Nome');
        $email = $this->ask('E-mail');
        $password = $this->secret('Senha');
        $passwordConfirmation = $this->secret('Confirme a senha');

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
                'password' => ['required', 'string', Password::min(8), 'confirmed'],
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
}
