<?php
/**
 * Door Game Manager - BBSLink Game Catalog
 */

class DoorManager {
    private static $games = [
        'lord' => [
            'name' => 'Legend of the Red Dragon',
            'description' => 'Classic fantasy RPG. Battle monsters, flirt at the inn, and become the greatest warrior in the land.',
            'category' => 'RPG',
        ],
        'lord2' => [
            'name' => 'LORD II: The New World',
            'description' => 'Sequel to LORD. Explore a vast new world with quests, puzzles, and adventures.',
            'category' => 'RPG',
        ],
        'tw' => [
            'name' => 'TradeWars 2002',
            'description' => 'Space trading and combat. Build an empire among the stars through trade, diplomacy, or war.',
            'category' => 'Strategy',
        ],
        'teos' => [
            'name' => 'Planets: TEOS',
            'description' => 'Space exploration and conquest. Colonize planets, build fleets, and dominate the galaxy.',
            'category' => 'Strategy',
        ],
        'ooii' => [
            'name' => 'Operation: Overkill II',
            'description' => 'Post-apocalyptic RPG. Survive in a wasteland overrun by mutants and hostile forces.',
            'category' => 'RPG',
        ],
        'usrp' => [
            'name' => 'Usurper',
            'description' => 'Medieval RPG. Fight monsters, join guilds, and scheme your way to the throne.',
            'category' => 'RPG',
        ],
        'bre' => [
            'name' => 'Barren Realms Elite',
            'description' => 'Strategic empire builder. Manage resources, raise armies, and conquer rival kingdoms.',
            'category' => 'Strategy',
        ],
        'bbsc' => [
            'name' => 'BBS Crash',
            'description' => 'Competitive hacking sim. Crash other BBSes while defending your own.',
            'category' => 'Strategy',
        ],
        'dmud' => [
            'name' => 'DoorMUD',
            'description' => 'Multi-user dungeon. Explore, fight, and interact with other players in a persistent text world.',
            'category' => 'RPG',
        ],
        'menu' => [
            'name' => 'BBSLink Menu',
            'description' => 'Browse the full BBSLink game directory and pick from all available titles.',
            'category' => 'Menu',
        ],
    ];

    /**
     * Get all available BBSLink games
     */
    public function getAvailableGames() {
        $games = [];
        foreach (self::$games as $code => $game) {
            $games[] = array_merge(['code' => $code], $game);
        }
        return $games;
    }

    /**
     * Check if a door code is valid
     */
    public function isValidDoor($code) {
        return isset(self::$games[$code]);
    }

    /**
     * Get a single game by code
     */
    public function getGame($code) {
        if (!$this->isValidDoor($code)) {
            return null;
        }
        return array_merge(['code' => $code], self::$games[$code]);
    }
}
