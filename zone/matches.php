<?php

/**
 *
 * nC Zone. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Marian Cepok, https://new-chapter.eu
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eru\nczone\zone;

use eru\nczone\utility\db_util;
use eru\nczone\utility\number_util;

/**
 * nC Zone matches management class.
 */
class matches {
    /** @var driver_interface */
    private $db;
    /** @var string */
    private $matches_table;
    /** @var string */
    private $match_teams_table;
    /** @var string */
    private $match_players_table;
    /** @var string */
    private $match_civs_table;
    /** @var string */
    private $team_civs_table;
    /** @var string */
    private $player_civs_table;
    /** @var string */
    private $maps_table;
    /** @var string */
    private $player_map_time;
    
    /**
     * Constructor
     * 
     * @param \phpbb\db\driver\driver_interface  $db                       Database object
     * @param string                             $matches_table            Name of the matches table
     * @param string                             $match_teams_table        Name of the table for the players of the teams
     * @param string                             $match_players_table      Name of the table for the teams
     * @param string                             $match_civs_table         Name of the match civs table
     * @param string                             $team_civs_table          Name of the team civs table
     * @param string                             $match_player_civs_table  Name of the player civs table
     * @param string                             $maps_table               Name of the maps table
     * @param string                             $player_map_table         Name of the table with the last time a player played a map
     */
    public function __construct(\phpbb\db\driver\driver_interface $db, $matches_table, $match_teams_table, $match_players_table, $match_civs_table, $team_civs_table, $match_player_civs_table, $maps_table, $player_map_table, $map_civs_table, $player_civ_table)
    {
        $this->db = $db;
        $this->matches_table = $matches_table;
        $this->match_teams_table = $match_teams_table;
        $this->match_players_table = $match_players_table;
        $this->match_civs_table = $match_civs_table;
        $this->team_civs_table = $team_civs_table;
        $this->match_player_civs_table = $match_player_civs_table;
        $this->maps_table = $maps_table;
        $this->player_map_table = $player_map_table;
        $this->map_civs_table = $map_civs_table;
        $this->player_civ_table = $player_civ_table;
    }

    /**
     * Creates a match and returns the match id
     * 
     * @param int    $draw_user_id    ID of the user who draw the game
     * @param array  $team1           Array of the players in team 1
     * @param array  $team2           Array of the players in team 2
     * @param int    $map_id          ID of the map to be played
     * @param array  $match_civ_ids   Array of civ ids for the whole match
     * @param array  $team1_civ_ids   Array of civ ids for team 1 only
     * @param array  $team2_civ_ids   Array of civ ids for team 2 only
     * @param array  $player_civ_ids  Array (user id => civ id) of civs for players
     * 
     * @return int
     */
    public function create_match(int $draw_user_id, $team1, $team2, int $map_id=0, $match_civ_ids=[], $team1_civ_ids=[], $team2_civ_ids=[], $player_civ_ids=[]): int
    {
        $match_id = (int)db_util::insert($this->db, $this->matches_table, [
            'match_id' => '',
            'draw_user_id' => (int)$draw_user_id,
            'post_user_id' => 0,
            'draw_time' => time(),
            'post_time' => 0,
            'winner_team_id' => 0,
        ]);

        $team1_id = (int)db_util::insert($this->db, $this->match_teams_table, [
            'team_id' => '',
            'match_id' => $match_id,
        ]);
        $team2_id = (int)db_util::insert($this->db, $this->match_teams_table, [
            'team_id' => '',
            'match_id' => $match_id,
        ]);

        $team_data = [];
        foreach($team1 as $player)
        {
            $team_data[] = [
                'team_id' => $team1_id,
                'user_id' => $player['id'],
                'draw_rating' => $player['rating'],
            ];
        }
        foreach($team2 as $player)
        {
            $team_data[] = [
                'team_id' => $team2_id,
                'user_id' => $player['id'],
                'draw_rating' => $player['rating'],
            ];
        }
        if(!empty($team_data))
        {
            $this->db->multi_insert($this->match_players_table, $team_data);
        }

        
        $match_civ_data = [];
        $match_civ_numbers = array_count_values($match_civ_ids);
        $unique_match_civ_ids = array_unique($match_civ_ids);
        foreach($civ_id as $unique_match_civ_ids)
        {
            $match_civ_data[] = [
                'match_id' => $match_id,
                'civ_id' => $civ_id,
                'number' => $match_civ_numbers[$civ_id],
            ];
        }
        if(!empty($match_civ_data))
        {
            $this->db->multi_insert($this->match_civs_table, $match_civ_data);
        }

        
        $team_civ_data = [];
        $team1_civ_numbers = array_count_values($team1_civ_numbers);
        $unique_team1_civ_numbers = array_unique($team1_civ_ids);
        foreach($civ_id as $unique_team1_civ_numbers)
        {
            $team_civ_data[] = [
                'team_id' => $team1_id,
                'civ_id' => (int)$civ_id,
                'number' => $team1_civ_numbers[$civ_id],
            ];
        }
        $team2_civ_numbers = array_count_values($team2_civ_numbers);
        $unique_team2_civ_numbers = array_unique($team2_civ_ids);
        foreach($civ_id as $unique_team2_civ_numbers)
        {
            $team_civ_data[] = [
                'team_id' => $team2_id,
                'civ_id' => (int)$civ_id,
                'number' => $team2_civ_numbers[$civ_id],
            ];
        }
        if(!empty($team_civ_data))
        {
            $this->db->multi_insert($this->team_civs_table, $team_civ_data);
        }


        $player_civ_data = [];
        foreach($player_civ_ids as $user_id => $civ_id)
        {
            $player_civ_data[] = [
                'match_id' => $match_id,
                'user_id' => (int)$user_id,
                'civ_id' => (int)$civ_id,
            ];
        }
        if(!empty($player_civ_data))
        {
            $this->db->multi_insert($this->match_player_civs_table, $player_civ_data);
        }

        return $match_id;
    }

    /**
     * Calculates the next map (id) to be drawn for a group of players
     * 
     * @param array  $user_ids  Ids of the users
     * 
     * @return int
     */
    public function get_players_map_id($users)
    {
        $user_ids = [];
        foreach($users as $user)
        {
            $user_ids[] = $user['id'];
        }

        return (int)db_util::get_var($this->db, [
            'SELECT' => 't.map_id, SUM(' . time() . ' - t.time) * m.weight AS val',
            'FROM' => [$this->player_map_table => 't', $this->maps_table => 'm'],
            'WHERE' => 't.map_id = m.map_id AND ' . $this->db->sql_in_set('t.user_id', $user_ids),
            'GROUP_BY' => 't.map_id',
            'ORDER_BY' => 'val DESC'
        ]);
    }

    public function get_match_civs($map_id, $users, $num_civs=0, $extra_civs=4)
    {
        if($num_civs == 0)
        {
            $num_civs = count($users) / 2;
        }

        $civ_ids = [];
        $civ_multiplier = [];


        $user_ids = [];
        foreach($users as $user)
        {
            $user_ids[] = $user['id'];
        }

        // first, get one of the force draw civs
        $force_civ = db_util::get_row($this->db, [
            'SELECT' => 'c.civ_id AS id, c.multiplier AS multiplier',
            'FROM' => array($this->map_civs_table => 'c', $this->player_civ_table => 'p'),
            'WHERE' => 'c.civ_id = p.civ_id AND c.force_draw AND NOT c.prevent_draw AND '. $this->db->sql_in_set('p.user_id', $user_ids),
            'GROUP_BY' => 'c.civ_id',
            'ORDER_BY' => 'SUM(' . time() . ' - p.time) DESC',
        ]);
        if($force_civ)
        {
            $force_civ_num = 1;
            $sql_add = ' AND c.civ_id != ' . $force_civ['id'];
            $test_civ_add = [$force_civ];
        }
        else
        {
            $force_civ_num = 0;
            $sql_add = '';
            $test_civ_add = [];
        }

        // get additional civs
        $draw_civs = db_util::get_num_rows($this->db, [
            'SELECT' => 'c.civ_id AS id, c.multiplier AS multiplier',
            'FROM' => array($this->map_civs_table => 'c', $this->player_civ_table => 'p'),
            'WHERE' => 'c.civ_id = p.civ_id AND NOT c.prevent_draw' . $sql_add,
            'GROUP_BY' => 'c.civ_id',
            'ORDER_BY' => 'SUM(' . time() . ' - p.time) DESC',
        ], $num_civs - count($civ_ids) + $extra_civs);

        if($extra_civs == 0)
        {
            $best_civs = array_merge($test_civ_add, $draw_civs);
            usort($best_civs, [__CLASS__, 'cmp_multiplier']);
        }
        else
        {
            // we drawed some extra civs and now we drop some to reduce the difference of multipliers
            $best_civs = [];
            $best_value = -1;
            for($i = 0; $i < $extra_civs; $i++)
            {
                $test_civs = array_merge($test_civ_add, array_slice($draw_civs, $i, $num_civs - $force_civ_num));
                usort($test_civs, [__CLASS__, 'cmp_multiplier']);
                $value = $test_civs[0]['multiplier'] - $test_civs[-1]['multiplier'];
                if($value < $best_value || $best_value < 0)
                {
                    $best_civs = $test_civs;
                    $best_value = $value;
                }
            }
        }

        return $best_civs;
    }

    public function get_teams_civs($map_id, $team1_users, $team2_users, $num_civs=0, $extra_civs=2)
    {
        if($num_civs == 0)
        {
            $num_civs = count($team1_users);
        }


        $team1_sum_rating = array_reduce($team1_users, [__CLASS__, 'rating_sum']);
        $team2_sum_rating = array_reduce($team2_users, [__CLASS__, 'rating_sum']);


        // we use some extra civs to be able to draw fair civs for the teams
        $team1_civpool = $this->get_match_civs($map_id, $team1_users, $num_civs + $extra_civs, 0);
        $team2_civpool = $this->get_match_civs($map_id, $team2_users, $num_civs + $extra_civs, 0);


        // get all index combinations with length $num_civs for our civpools
        $test_indices = [];
        for($i = 0; $i < $extra_civs + 1; $i++)
        {
            $test_indices[] = [$i];
        }
        for($i = 1; $i < $num_civs; $i++)
        {
            $temp = [];
            foreach($test_indices as $lo)
            {
                for($j = end($lo)+1; $j < $num_civs + $extra_civs; $j++)
                {
                    $temp[] = array_merge($lo, [$j]);
                }
            }
            $test_indices = $temp;
        }

        
        $best_indices = [[], []];
        $best_value = -1;

        // we test all possible combinations and the combination, which minimizes |diff(player_ratings * multipliers)|
        foreach($test_indices as $team1_indices)
        {
            $team1_sum_multiplier = 0;
            foreach($team1_indices as $index)
            {
                $team1_sum_multiplier += $team1_civpool[$index]['multiplier'];
            }

            foreach($test_indices as $team2_indices)
            {
                $team2_sum_multiplier = 0;
                foreach($team2_indices as $index)
                {
                    $team2_sum_multiplier += $team2_civpool[$index]['multiplier'];
                }

                $value = abs($team1_sum_rating * $team1_sum_multiplier - $team2_sum_rating * $team2_sum_multiplier);
                if($value < $best_value || $best_value < 0)
                {
                    $best_indices[0] = $team1_indices;
                    $best_indices[1] = $team2_indices;
                    $best_value = $value;
                }
            }
        }


        // apply the indices on the civpools
        $team1_civs = [];
        $team2_civs = [];
        foreach($best_indices[0] as $index)
        {
            $team1_civs[] = $team1_civpool[$index];
        }
        foreach($best_indices[1] as $index)
        {
            $team2_civs[] = $team2_civpool[$index];
        }


        return [$team1_civs, $team2_civs];
    }

    public static function cmp_multiplier($c1, $c2): int
    {
        return number_util::cmp($c1['multiplier'], $c2['multiplier']);
    }

    public static function rating_sum($c, $p): int
    {
        return $c + $p['rating'];
    }
}
