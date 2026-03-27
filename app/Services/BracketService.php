<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BracketService
{
    public function makeTree(Tournament $tournament): array
    {
        if ($tournament->matches()->exists()) {
            throw new InvalidArgumentException('Bracket already generated for this tournament.');
        }

        $playersList = $tournament->registrations()->pluck('user_id')->toArray();
        shuffle($playersList);

        $totalPlayers = count($playersList);

        if ($totalPlayers < 2 || ($totalPlayers & ($totalPlayers - 1)) !== 0) {
            throw new InvalidArgumentException("Player count ({$totalPlayers}) must be a power of 2 (e.g. 2, 4, 8, 16...).");
        }

        $totalRounds = (int) log($totalPlayers, 2);

        return DB::transaction(function () use ($tournament, $playersList, $totalPlayers, $totalRounds) {
            $allRoundMatches = [];

            for ($round = $totalRounds; $round >= 1; $round--) {
                $matchesInThisRound = $totalPlayers / pow(2, $round);
                $allRoundMatches[$round] = [];

                for ($position = 1; $position <= $matchesInThisRound; $position++) {
                    $nextMatchId = null;

                    if ($round < $totalRounds) {
                        $parentPosition = ceil($position / 2);
                        $nextMatchId = $allRoundMatches[$round + 1][$parentPosition - 1]->id;
                    }

                    $allRoundMatches[$round][] = GameMatch::create([
                        'tournament_id'  => $tournament->id,
                        'round_number'   => $round,
                        'match_position' => $position,
                        'next_match_id'  => $nextMatchId,
                        'status'         => 'pending',
                    ]);
                }
            }

            $playerIndex = 0;
            foreach ($allRoundMatches[1] as $firstRoundMatch) {
                $firstRoundMatch->update([
                    'player1_id' => $playersList[$playerIndex],
                    'player2_id' => $playersList[$playerIndex + 1],
                ]);
                $playerIndex += 2;
            }

            return $allRoundMatches;
        });
    }
}
