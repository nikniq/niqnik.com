<?php

namespace App\Services;

use App\Models\GameClient;
use App\Models\GameEvent;
use App\Models\PlayerGameStat;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameStatsService
{
    /**
     * Store one event and fold it into the player's aggregate for that game.
     */
    public function record(GameClient $client, User $player, array $data): GameEvent
    {
        return DB::transaction(function () use ($client, $player, $data) {
            $occurredAt = isset($data['occurred_at']) ? now()->parse($data['occurred_at']) : now();

            $event = GameEvent::create([
                'game_client_id' => $client->id,
                'user_id' => $player->id,
                'event' => $data['event'],
                'score' => $data['score'] ?? null,
                'level' => $data['level'] ?? null,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'won' => $data['won'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'occurred_at' => $occurredAt,
            ]);

            $stat = PlayerGameStat::query()
                ->where('game_client_id', $client->id)
                ->where('user_id', $player->id)
                ->lockForUpdate()
                ->first();

            if (! $stat) {
                $stat = new PlayerGameStat([
                    'game_client_id' => $client->id,
                    'user_id' => $player->id,
                    'first_played_at' => $occurredAt,
                ]);
            }

            $stat->events += 1;
            if (in_array($data['event'], config('sso.play_events', []), true)) {
                $stat->plays += 1;
            }
            if (! empty($data['won'])) {
                $stat->wins += 1;
            }
            if (isset($data['score'])) {
                $stat->total_score += (int) $data['score'];
                if ($stat->best_score === null || (int) $data['score'] > $stat->best_score) {
                    $stat->best_score = (int) $data['score'];
                }
            }
            if (isset($data['duration_seconds'])) {
                $stat->total_duration_seconds += (int) $data['duration_seconds'];
            }
            if (! empty($data['level'])) {
                $stat->last_level = $data['level'];
            }
            if ($stat->first_played_at === null || $occurredAt->lt($stat->first_played_at)) {
                $stat->first_played_at = $occurredAt;
            }
            if ($stat->last_played_at === null || $occurredAt->gt($stat->last_played_at)) {
                $stat->last_played_at = $occurredAt;
            }
            $stat->save();

            return $event;
        });
    }

    public function statsFor(GameClient $client, User $player): ?PlayerGameStat
    {
        return PlayerGameStat::where('game_client_id', $client->id)->where('user_id', $player->id)->first();
    }

    public function leaderboard(GameClient $client, int $limit): Collection
    {
        $limit = max(1, min($limit, (int) config('sso.leaderboard_max', 100)));

        return PlayerGameStat::with('user:id,name,avatar')
            ->where('game_client_id', $client->id)
            ->whereNotNull('best_score')
            ->orderByDesc('best_score')
            ->orderBy('last_played_at')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn (PlayerGameStat $s, int $i) => [
                'rank' => $i + 1,
                'player' => [
                    'id' => $s->user_id,
                    'name' => $s->user?->name,
                    'avatar' => $s->user?->avatar,
                ],
                'best_score' => $s->best_score,
                'plays' => $s->plays,
                'wins' => $s->wins,
                'last_played_at' => $s->last_played_at?->toIso8601String(),
            ]);
    }

    /**
     * Rank of a player on a game's leaderboard (1-based), or null if unranked.
     */
    public function rankFor(GameClient $client, User $player): ?int
    {
        $mine = $this->statsFor($client, $player);
        if (! $mine || $mine->best_score === null) {
            return null;
        }

        return 1 + PlayerGameStat::where('game_client_id', $client->id)
            ->whereNotNull('best_score')
            ->where('best_score', '>', $mine->best_score)
            ->count();
    }
}
