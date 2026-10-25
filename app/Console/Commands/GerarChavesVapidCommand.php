<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GerarChavesVapidCommand extends Command
{
    protected $signature = 'webpush:vapid
                            {--show : Apenas exibe as chaves sem gravar no .env}
                            {--force : Sobrescreve chaves já existentes no .env}';

    protected $description = 'Gera o par de chaves VAPID usado no Web Push (notificações com o PWA fechado)';

    public function handle(): int
    {
        $chaves = VAPID::createVapidKeys();

        if ($this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY='.$chaves['publicKey']);
            $this->line('VAPID_PRIVATE_KEY='.$chaves['privateKey']);

            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $this->error('Arquivo .env não encontrado.');

            return self::FAILURE;
        }

        $conteudo = (string) file_get_contents($envPath);

        if (preg_match('/^VAPID_PUBLIC_KEY=.+$/m', $conteudo) && ! $this->option('force')) {
            $this->warn('Chaves VAPID já existem no .env. Use --force para regenerar (os aparelhos precisarão se inscrever novamente).');

            return self::SUCCESS;
        }

        foreach (['VAPID_PUBLIC_KEY' => $chaves['publicKey'], 'VAPID_PRIVATE_KEY' => $chaves['privateKey']] as $chave => $valor) {
            if (preg_match("/^{$chave}=.*$/m", $conteudo)) {
                $conteudo = (string) preg_replace("/^{$chave}=.*$/m", "{$chave}={$valor}", $conteudo);
            } else {
                $conteudo = rtrim($conteudo).PHP_EOL."{$chave}={$valor}".PHP_EOL;
            }
        }

        file_put_contents($envPath, $conteudo);
        $this->call('config:clear');

        $this->info('Chaves VAPID geradas e gravadas no .env com sucesso.');

        return self::SUCCESS;
    }
}
