<?php
/**
 *
 * nC Zone. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Marian Cepok, https://new-chapter.eu
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eru\nczone\acp;

use eru\nczone\utility\phpbb_util;

/**
 * nC Zone ACP module.
 */
class main_module
{
    public $page_title;
    public $tpl_name;
    public $u_action;

    public function main($id, $mode)
    {
        $request = phpbb_util::request();
        $language = phpbb_util::language();
        $language->add_lang('common', 'eru/nczone');
        $language->add_lang('info_acp', 'eru/nczone');
        $this->page_title = $language->lang('ACP_NCZONE_TITLE');
        add_form_key('eru/nczone');

        $submit = False;
        if ($request->is_set_post('submit')) {
            if (!check_form_key('eru/nczone')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }

            $submit = True;
        }

        switch($mode)
        {
            case 'general':
                $this->general($submit);
                $this->tpl_name = 'acp_nczone_general';
                break;
            case 'draw':
                $this->draw($submit);
                $this->tpl_name = 'acp_nczone_draw';
                break;
        }
    }

    public function draw(bool $submit): void
    {
        $request = phpbb_util::request();
        $config = phpbb_util::config();
        $language = phpbb_util::language();

        if($submit)
        {
            $config->set('nczone_draw_player_civs', (int)$request->variable('nczone_draw_player_civs', 600));
            $config->set('nczone_draw_team_civs', (int)$request->variable('nczone_draw_team_civs', 120));
            $config->set('nczone_draw_match_extra_civs_1vs1', (int)$request->variable('nczone_draw_match_extra_civs_1vs1', 7));
            $config->set('nczone_draw_match_extra_civs_2vs2', (int)$request->variable('nczone_draw_match_extra_civs_2vs2', 6));
            $config->set('nczone_draw_match_extra_civs_3vs3', (int)$request->variable('nczone_draw_match_extra_civs_3vs3', 5));
            $config->set('nczone_draw_match_extra_civs_4vs4', (int)$request->variable('nczone_draw_match_extra_civs_4vs4', 4));
            $config->set('nczone_draw_team_extra_civs_1vs1', (int)$request->variable('nczone_draw_team_extra_civs_1vs1', 5));
            $config->set('nczone_draw_team_extra_civs_2vs2', (int)$request->variable('nczone_draw_team_extra_civs_2vs2', 4));
            $config->set('nczone_draw_team_extra_civs_3vs3', (int)$request->variable('nczone_draw_team_extra_civs_3vs3', 3));
            $config->set('nczone_draw_team_extra_civs_4vs4', (int)$request->variable('nczone_draw_team_extra_civs_4vs4', 2));
            $player_num_civs_1vs1 = (int)$request->variable('nczone_draw_player_num_civs_1vs1', 9);
            $player_num_civs_2vs2 = (int)$request->variable('nczone_draw_player_num_civs_2vs2', 5);
            $player_num_civs_3vs3 = (int)$request->variable('nczone_draw_player_num_civs_3vs3', 4);
            $player_num_civs_4vs4 = (int)$request->variable('nczone_draw_player_num_civs_4vs4', 3);
            if($player_num_civs_1vs1 >= 1 && $player_num_civs_2vs2 >= 1 && $player_num_civs_3vs3 >= 1 && $player_num_civs_4vs4 >= 1)
            {
                $config->set('nczone_draw_player_num_civs_1vs1', $player_num_civs_1vs1);
                $config->set('nczone_draw_player_num_civs_2vs2', $player_num_civs_2vs2);
                $config->set('nczone_draw_player_num_civs_3vs3', $player_num_civs_3vs3);
                $config->set('nczone_draw_player_num_civs_4vs4', $player_num_civs_4vs4);
            } // todo: else

            trigger_error($language->lang('ACP_NCZONE_SAVED') . adm_back_link($this->u_action));
        }

        phpbb_util::template()->assign_vars(array(
            'U_ACTION' => $this->u_action,
            'nczone_rules_post_id' => $config['nczone_rules_post_id'],
            'nczone_draw_player_civs' => $config['nczone_draw_player_civs'],
            'nczone_draw_team_civs' => $config['nczone_draw_team_civs'],
            'nczone_draw_match_extra_civs_1vs1' => $config['nczone_draw_match_extra_civs_1vs1'],
            'nczone_draw_match_extra_civs_2vs2' => $config['nczone_draw_match_extra_civs_2vs2'],
            'nczone_draw_match_extra_civs_3vs3' => $config['nczone_draw_match_extra_civs_3vs3'],
            'nczone_draw_match_extra_civs_4vs4' => $config['nczone_draw_match_extra_civs_4vs4'],
            'nczone_draw_team_extra_civs_1vs1' => $config['nczone_draw_team_extra_civs_1vs1'],
            'nczone_draw_team_extra_civs_2vs2' => $config['nczone_draw_team_extra_civs_2vs2'],
            'nczone_draw_team_extra_civs_3vs3' => $config['nczone_draw_team_extra_civs_3vs3'],
            'nczone_draw_team_extra_civs_4vs4' => $config['nczone_draw_team_extra_civs_4vs4'],
            'nczone_draw_player_num_civs_1vs1' => $config['nczone_draw_player_num_civs_1vs1'],
            'nczone_draw_player_num_civs_2vs2' => $config['nczone_draw_player_num_civs_2vs2'],
            'nczone_draw_player_num_civs_3vs3' => $config['nczone_draw_player_num_civs_3vs3'],
            'nczone_draw_player_num_civs_4vs4' => $config['nczone_draw_player_num_civs_4vs4'],
        ));
    }

    public function general(bool $submit): void
    {
        $request = phpbb_util::request();
        $config = phpbb_util::config();
        $language = phpbb_util::language();

        if($submit)
        {
            $config->set('nczone_rules_post_id', (int)$request->variable('nczone_rules_post_id', 0));
            $config->set('nczone_match_forum_id', (int)$request->variable('nczone_match_forum_id', 0));
            $config->set('nczone_bet_time', (int)$request->variable('nczone_bet_time', 1200));
            $config->set('nczone_info_posts', join(',', array_map(function($a) { return (int)$a; }, preg_split('/$\R?^/m', $request->variable('nczone_info_posts', '')))));

            trigger_error($language->lang('ACP_NCZONE_SAVED') . adm_back_link($this->u_action));
        }

        phpbb_util::template()->assign_vars(array(
            'U_ACTION' => $this->u_action,
            'nczone_rules_post_id' => $config['nczone_rules_post_id'],
            'nczone_match_forum_id' => $config['nczone_match_forum_id'],
            'nczone_bet_time' => $config['nczone_bet_time'],
            'nczone_info_posts' => str_replace(',', "\n", $config['nczone_info_posts']),
        ));
    }
}
