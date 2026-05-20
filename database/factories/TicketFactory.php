<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status'      => fake()->randomElement(TicketStatus::cases()),
            'priority'    => fake()->randomElement(TicketPriority::cases()),
            'due_date'    => fake()->optional()->dateTimeBetween('-1 month', '+3 months'),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(1)->toDateString(),
            'status'   => TicketStatus::Todo,
        ]);
    }

    public function withStatus(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function withPriority(TicketPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }
}
