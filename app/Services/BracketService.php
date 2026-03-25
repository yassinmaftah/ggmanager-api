<?php

namespace App\Services;

use App\Models\GameMatch;

class BracketService
{
    public function makeTree($tournament)
    {
        // Step 1: get all registered players and shuffle them randomly
        $playersList = $tournament->registrations()->pluck('user_id')->toArray();
        shuffle($playersList);

        $totalPlayers = count($playersList);

        // Step 2: figure out how many rounds we need
        // Example: 8 players = 3 rounds (8->4->2->1)
        $totalRounds = log($totalPlayers, 2);

        // Step 3: build the bracket backwards (Final first, then Semi-finals, etc.)
        // We store each round's matches in an array so we can link them later
        $allRoundMatches = [];

        // Create matches round by round starting from the LAST round (the Final)
        for ($round = $totalRounds; $round >= 1; $round--) {

            // How many matches are in this round?
            // Round 3 (Final) = 1 match, Round 2 (Semi) = 2 matches, Round 1 = 4 matches
            $matchesInThisRound = $totalPlayers / pow(2, $round);

            $allRoundMatches[$round] = [];

            for ($position = 1; $position <= $matchesInThisRound; $position++) {

                // For rounds after round 1, find the parent match in the next round
                // Every 2 matches feed into 1 match in the next round
                $nextMatchId = null;

                if ($round < $totalRounds) {
                    $parentPosition = ceil($position / 2);
                    $nextMatchId = $allRoundMatches[$round + 1][$parentPosition - 1]->id;
                }

                $newMatch = GameMatch::create([
                    'tournament_id'  => $tournament->id,
                    'round_number'   => $round,
                    'match_position' => $position,
                    'next_match_id'  => $nextMatchId,
                    'player1_id'     => null,
                    'player2_id'     => null,
                    'status'         => 'pending',
                ]);

                $allRoundMatches[$round][] = $newMatch;
            }
        }

        // Step 4: now fill in the real players into Round 1 matches
        $playerIndex = 0;

        foreach ($allRoundMatches[1] as $firstRoundMatch) {
            $firstRoundMatch->update([
                'player1_id' => $playersList[$playerIndex],
                'player2_id' => $playersList[$playerIndex + 1],
            ]);

            $playerIndex += 2;
        }
    }
}
