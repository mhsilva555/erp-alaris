---
name: laravel-workflow
description: >-
  Standard engineering workflows, commands, and testing procedures for the Laravel 13, Pest PHP,
  and Tailwind CSS v4 stack in ERP Alaris. Use this skill when executing migrations, running tests,
  formatting code with Pint, or debugging asset compilation.
---

# Laravel Workflow Skill

This skill contains the operational runbook for development, testing, styling, and database tasks in the ERP Alaris application.

---

## 1. Quick Reference Commands

| Task | Command | Notes |
| :--- | :--- | :--- |
| **Start Dev Environment** | `composer run dev` | Runs Artisan server, queue worker, and Vite concurrently |
| **Run Pest Tests** | `composer run test` ou `php artisan test` | Executar dentro do WSL (`wsl -d Ubuntu-24.04`) para performance nativa sem lock |
| **Format Code** | `vendor/bin/pint` | Fixes code style to PSR-12 / Laravel standards |
| **Run Migrations** | `php artisan migrate` | Executes pending migrations |
| **Re-seed Database** | `php artisan migrate:fresh --seed` | Re-creates database and executes `DatabaseSeeder` |
| **Build Frontend** | `npm run build` | Compiles CSS and JS into `public/build` |
| **Clear App Caches** | `php artisan optimize:clear` | Clears route, view, and config caches |
| **Install Scheduler Cron**| `php artisan cron:install` | Configures minute-by-minute cron in crontab or Windows Task Scheduler |
| **Check Cron Status** | `php artisan cron:install --status` | Verifies active cron schedule status |
| **Run Agenda Notifications** | `php artisan agendamentos:verificar-notificacoes` | Manually triggers upcoming events scan |

---

## 2. Testing Guidelines (Pest PHP)

1. Test files live in `tests/Feature/` and `tests/Unit/`.
2. When creating a test for a controller or model:
   ```php
   use App\Models\User;
   use App\Models\Cliente;

   it('allows authenticated users to view clientes', function () {
       $user = User::factory()->create();
       
       $response = $this->actingAs($user)->get(route('clientes.index'));

       $response->assertOk();
   });
   ```
3. Always verify tests pass before submitting code modifications:
   ```bash
   php artisan test
   ```

---

## 3. Styling & Frontend Guidelines (Tailwind v4)

1. **Theme Tokens**:
   - Primary blues: `var(--color-primary-50)` through `var(--color-primary-900)`.
   - Surfaces: `var(--color-surface-50)` through `var(--color-surface-950)`.
   - Semantic: `var(--color-success)`, `var(--color-warning)`, `var(--color-danger)`, `var(--color-info)`.
2. **Light vs Dark Mode**:
   - The document root receives `data-theme="dark"` or defaults to light.
   - Do NOT hardcode fixed dark hex colors on components; use the CSS variables or standard Tailwind classes referencing configured theme variables.
   - If an element must keep pure white text in both light and dark modes (e.g. badges, primary buttons), use the `.preserve-white` class.
3. **Compiling Assets**:
   - Vite is configured with `@tailwindcss/vite` and `laravel-vite-plugin`.
   - Run `npm run build` to verify that there are no CSS syntax errors or unclosed blocks.

---

## 4. Code Standards & Static Analysis

1. Before pushing or finishing changes, run:
   ```bash
   vendor/bin/pint --test
   ```
2. If styling issues are detected, auto-format using:
   ```bash
   vendor/bin/pint
   ```
