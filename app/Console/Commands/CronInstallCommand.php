<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Configuracao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CronInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:install
                            {--uninstall : Remove a configuração de cron / tarefa agendada do sistema}
                            {--status : Apenas verifica o status atual da instalação da cron}
                            {--force : Sobrescreve sem pedir confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala e configura a rotina cron no sistema operacional para executar o agendador e monitorar eventos a cada minuto';

    /**
     * Identificador padrão da tarefa agendada no Windows.
     */
    protected const TASK_NAME = 'ERP_Alaris_Cron';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $uninstall = (bool) $this->option('uninstall');
        $statusOnly = (bool) $this->option('status');

        if ($statusOnly) {
            return $this->exibirStatus();
        }

        if ($uninstall) {
            return $this->desinstalarCron();
        }

        return $this->instalarCron();
    }

    /**
     * Exibe o status da cron no sistema operacional e nas configurações.
     */
    protected function exibirStatus(): int
    {
        $this->info('--- Status da Cron do ERP Alaris ---');
        $instalada = Configuracao::get('cron_instalada', 'false') === 'true';
        $instaladaEm = Configuracao::get('cron_instalada_em', 'Nunca');
        $osFamily = PHP_OS_FAMILY;

        $this->line("Sistema Operacional: <comment>{$osFamily}</comment>");
        $this->line('Status registrado no ERP: '.($instalada ? '<info>Instalada e Ativa</info>' : '<comment>Não Instalada</comment>'));
        $this->line("Data da última instalação: {$instaladaEm}");

        if ($osFamily === 'Windows') {
            $output = [];
            exec('schtasks /query /tn "'.self::TASK_NAME.'" 2>&1', $output, $exitCode);
            if ($exitCode === 0) {
                $this->info("Tarefa Agendada no Windows ('".self::TASK_NAME."') encontrada e ativa!");
            } else {
                $this->warn("Tarefa Agendada no Windows ('".self::TASK_NAME."') não encontrada no Task Scheduler.");
            }
        } else {
            $crontab = shell_exec('crontab -l 2>/dev/null') ?? '';
            if (str_contains($crontab, 'schedule:run') || str_contains($crontab, 'agendamentos:verificar-notificacoes')) {
                $this->info('Entrada na Crontab do Linux/macOS encontrada e ativa!');
            } else {
                $this->warn('Nenhuma entrada referente ao ERP Alaris encontrada na crontab atual.');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Instala a cron no sistema operacional (Windows Task Scheduler ou Linux/macOS Crontab).
     */
    protected function instalarCron(): int
    {
        $basePath = base_path();
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $osFamily = PHP_OS_FAMILY;

        $this->info('=== Instalando Cron de Verificação Minuto a Minuto — ERP Alaris ===');
        $this->line("Diretório do projeto: <comment>{$basePath}</comment>");
        $this->line("Binário do PHP: <comment>{$phpBinary}</comment>");
        $this->line("SO Detectado: <comment>{$osFamily}</comment>");

        $sucesso = false;

        if ($osFamily === 'Windows') {
            $sucesso = $this->instalarNoWindows($phpBinary, $artisanPath);
        } else {
            $sucesso = $this->instalarNoLinux($phpBinary, $basePath);
        }

        if ($sucesso) {
            Configuracao::set('cron_instalada', 'true', 'Indica se a cron foi instalada no SO');
            Configuracao::set('cron_instalada_em', now()->format('Y-m-d H:i:s'), 'Data e hora da instalação da cron');
            Configuracao::set('cron_so', $osFamily, 'Sistema operacional onde a cron foi instalada');

            $this->newLine();
            $this->info('✓ Cron instalada com sucesso no sistema operacional!');
            $this->line('O agendador executará a cada 1 minuto o comando: <comment>php artisan schedule:run</comment>');
            $this->line('Isso acionará automaticamente a checagem de eventos no Agendamento.');

            $this->newLine();
            $this->info('Executando teste imediato de verificação de eventos...');
            Artisan::call('agendamentos:verificar-notificacoes', [], $this->output);

            return self::SUCCESS;
        }

        $this->error('Houve uma falha ao tentar registrar a cron automaticamente no sistema operacional.');
        $this->exibirInstrucoesManuais($phpBinary, $artisanPath, $basePath, $osFamily);

        return self::FAILURE;
    }

    /**
     * Instala Tarefa Agendada no Windows via schtasks.
     */
    protected function instalarNoWindows(string $phpBinary, string $artisanPath): bool
    {
        // Cria arquivo batch para execução limpa e compatível
        $batchDir = storage_path('app/cron');
        if (! is_dir($batchDir)) {
            mkdir($batchDir, 0755, true);
        }
        $batchFile = $batchDir.DIRECTORY_SEPARATOR.'run_scheduler.bat';
        $vbsFile = $batchDir.DIRECTORY_SEPARATOR.'run_scheduler_silent.vbs';

        // Conteúdo do .bat
        $batContent = "@echo off\r\n\"{$phpBinary}\" \"{$artisanPath}\" schedule:run >> \"".storage_path('logs/cron.log')."\" 2>&1\r\n";
        file_put_contents($batchFile, $batContent);

        // VBScript para executar em segundo plano sem piscar janela de prompt
        $vbsContent = 'CreateObject("Wscript.Shell").Run """'.str_replace('\\', '\\\\', $batchFile)."\"\"\", 0, False\r\n";
        file_put_contents($vbsFile, $vbsContent);

        $taskName = self::TASK_NAME;
        $command = "wscript.exe \"{$vbsFile}\"";

        // Cria a tarefa no Agendador de Tarefas do Windows a cada 1 minuto
        $cmdSchtasks = "schtasks /create /tn \"{$taskName}\" /tr \"{$command}\" /sc minute /mo 1 /f";

        $output = [];
        $exitCode = 0;
        exec($cmdSchtasks.' 2>&1', $output, $exitCode);

        if ($exitCode === 0) {
            $this->line("Tarefa Agendada criada no Windows: <info>{$taskName}</info>");
            $this->line("Script de execução silenciosa: <comment>{$vbsFile}</comment>");

            return true;
        }

        $this->warn('Aviso do schtasks: '.implode("\n", $output));

        return false;
    }

    /**
     * Instala entrada no crontab do Linux/macOS.
     */
    protected function instalarNoLinux(string $phpBinary, string $basePath): bool
    {
        try {
            $cronLine = '* * * * * cd '.escapeshellarg($basePath).' && '.escapeshellcmd($phpBinary).' artisan schedule:run >> /dev/null 2>&1';
            $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?? '';

            if (str_contains($currentCrontab, 'schedule:run') && str_contains($currentCrontab, $basePath)) {
                $this->info('A crontab já continha o agendador deste projeto configurado.');

                return true;
            }

            // Remove versões antigas do mesmo projeto se houver
            $lines = array_filter(explode("\n", $currentCrontab), function ($line) use ($basePath) {
                return ! (str_contains($line, 'schedule:run') && str_contains($line, $basePath));
            });

            $lines[] = '# ERP Alaris - Scheduler minuto a minuto';
            $lines[] = $cronLine;
            $newCrontab = implode("\n", array_filter($lines))."\n";

            $tempFile = tempnam(sys_get_temp_dir(), 'cron_alaris_');
            file_put_contents($tempFile, $newCrontab);

            $output = [];
            $exitCode = 0;
            exec('crontab '.escapeshellarg($tempFile).' 2>&1', $output, $exitCode);
            @unlink($tempFile);

            if ($exitCode === 0) {
                $this->line('Crontab atualizada com a linha:');
                $this->line("<comment>{$cronLine}</comment>");

                return true;
            }

            $this->warn('Aviso ao atualizar crontab: '.implode("\n", $output));

            return false;
        } catch (Throwable $e) {
            $this->error('Erro ao configurar crontab: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Remove a tarefa/cron do sistema.
     */
    protected function desinstalarCron(): int
    {
        $osFamily = PHP_OS_FAMILY;
        $this->warn('Desinstalando a cron do ERP Alaris...');

        if ($osFamily === 'Windows') {
            $taskName = self::TASK_NAME;
            $output = [];
            exec("schtasks /delete /tn \"{$taskName}\" /f 2>&1", $output, $exitCode);
            if ($exitCode === 0) {
                $this->info("Tarefa Agendada '{$taskName}' removida do Windows.");
            } else {
                $this->comment('A tarefa agendada não existia ou já havia sido removida.');
            }
        } else {
            $basePath = base_path();
            $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?? '';
            $lines = array_filter(explode("\n", $currentCrontab), function ($line) use ($basePath) {
                return ! (str_contains($line, $basePath) || str_contains($line, 'ERP Alaris'));
            });
            $newCrontab = implode("\n", array_filter($lines))."\n";

            $tempFile = tempnam(sys_get_temp_dir(), 'cron_alaris_');
            file_put_contents($tempFile, $newCrontab);
            exec('crontab '.escapeshellarg($tempFile).' 2>&1');
            @unlink($tempFile);
            $this->info('Linhas do ERP Alaris removidas da crontab.');
        }

        Configuracao::set('cron_instalada', 'false', 'Indica se a cron foi instalada no SO');
        $this->info('Desinstalação concluída com sucesso.');

        return self::SUCCESS;
    }

    /**
     * Exibe instruções manuais se a instalação automática falhar por falta de privilégio.
     */
    protected function exibirInstrucoesManuais(string $phpBinary, string $artisanPath, string $basePath, string $osFamily): void
    {
        $this->newLine();
        $this->line('<options=bold>Instrução para configuração manual:</>');

        if ($osFamily === 'Windows') {
            $this->line('No Prompt de Comando (CMD) como Administrador, execute:');
            $this->line('<comment>schtasks /create /tn "'.self::TASK_NAME."\" /tr \"\\\"{$phpBinary}\\\" \\\"{$artisanPath}\\\" schedule:run\" /sc minute /mo 1 /f</comment>");
        } else {
            $this->line('Abra seu crontab com <comment>crontab -e</comment> e adicione:');
            $this->line("<comment>* * * * * cd \"{$basePath}\" && \"{$phpBinary}\" artisan schedule:run >> /dev/null 2>&1</comment>");
        }
    }
}
