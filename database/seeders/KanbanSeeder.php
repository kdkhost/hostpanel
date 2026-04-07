<?php

namespace Database\Seeders;

use App\Models\KanbanBoard;
use App\Models\KanbanColumn;
use Illuminate\Database\Seeder;

class KanbanSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            [
                'name' => 'Suporte / Tickets',
                'type' => 'tickets',
                'columns' => [
                    ['name' => 'Aberto',         'color' => '#ef4444', 'mapped_status' => 'open',           'sort_order' => 1],
                    ['name' => 'Em Andamento',    'color' => '#f59e0b', 'mapped_status' => 'in_progress',    'sort_order' => 2],
                    ['name' => 'Aguardando',      'color' => '#8b5cf6', 'mapped_status' => 'on_hold',        'sort_order' => 3],
                    ['name' => 'Respondido',      'color' => '#3b82f6', 'mapped_status' => 'answered',       'sort_order' => 4],
                    ['name' => 'Fechado',         'color' => '#6b7280', 'mapped_status' => 'closed',         'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Tarefas Internas',
                'type' => 'tasks',
                'columns' => [
                    ['name' => 'Backlog',          'color' => '#9ca3af', 'mapped_status' => null, 'sort_order' => 1],
                    ['name' => 'A Fazer',          'color' => '#ef4444', 'mapped_status' => null, 'sort_order' => 2],
                    ['name' => 'Em Progresso',     'color' => '#f59e0b', 'mapped_status' => null, 'sort_order' => 3],
                    ['name' => 'Revisão',          'color' => '#3b82f6', 'mapped_status' => null, 'sort_order' => 4],
                    ['name' => 'Concluído',        'color' => '#10b981', 'mapped_status' => null, 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Provisionamentos',
                'type' => 'provisioning',
                'columns' => [
                    ['name' => 'Pendente',         'color' => '#f59e0b', 'mapped_status' => 'pending',    'sort_order' => 1],
                    ['name' => 'Processando',      'color' => '#3b82f6', 'mapped_status' => 'processing', 'sort_order' => 2],
                    ['name' => 'Ativo',            'color' => '#10b981', 'mapped_status' => 'active',     'sort_order' => 3],
                    ['name' => 'Falhou',           'color' => '#ef4444', 'mapped_status' => 'failed',     'sort_order' => 4],
                ],
            ],
        ];

        foreach ($boards as $boardData) {
            $columns = $boardData['columns'];
            unset($boardData['columns']);

            $board = KanbanBoard::firstOrCreate(['type' => $boardData['type']], array_merge($boardData, ['active' => true]));

            foreach ($columns as $col) {
                KanbanColumn::firstOrCreate(
                    ['kanban_board_id' => $board->id, 'name' => $col['name']],
                    array_merge($col, ['kanban_board_id' => $board->id])
                );
            }
        }

        $this->command->info('Kanban boards seeded.');
    }
}
