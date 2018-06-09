<?php
/**
 *
 * nC Zone. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, Marian Cepok, https://new-chapter.eu
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace eru\nczone\mcp;

/**
 * nC Zone MCP module.
 */
class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $template, $user, $phpbb_container, $request;
		global $zone_players, $zone_civs, $zone_maps;

		$zone_players = $phpbb_container->get('eru.nczone.zone.players');
		$zone_civs = $phpbb_container->get('eru.nczone.zone.civs');
		$zone_maps = $phpbb_container->get('eru.nczone.zone.maps');

		$user->add_lang_ext('eru/nczone', 'info_mcp');
		$this->tpl_name = 'mcp_zone_body';
		$this->page_title = $user->lang('MCP_ZONE_TITLE');
		add_form_key('eru/nczone');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('eru/nczone'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}
		}

		switch($mode)
		{
			case 'players':
				$this->players();
			break;

			case 'civs':
				$this->civs();
			break;

			case 'maps':
				$this->maps();
			break;
		}

		$template->assign_var('U_POST_ACTION', $this->u_action);
	}

	function players()
	{
		global $template, $request, $db, $phpbb_root_path, $phpEx;
		global $zone_players;

		$template->assign_var('S_MANAGE_PLAYERS', true);

		$user_id = $request->variable('user_id', '');
		if($user_id == '')
		{
			$username = $request->variable('username', '');
			$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
			$result = $db->sql_query($sql);
			$user_id = (int) $db->sql_fetchfield('user_id');
			$db->sql_freeresult($result);
		}

		$new_player = $request->variable('new_player', '0') == '1';
		$edit_player = $request->variable('edit_player', '0') == '1';
		if($new_player)
		{
			$activate = $request->variable('activate', '') == 'on';
			if($activate)
			{
				$rating = $request->variable('rating', 0);
				$zone_players->activate_player($user_id, $rating);
			}
		}
		elseif($edit_player)
		{
			$rating = $request->variable('rating', 0);
			$zone_players->edit_player($user_id, array('rating' => $rating));
		}
		else
		{
			if($user_id)
			{
				$template->assign_var('USER_ID', $user_id);

				$player = $zone_players->get_player($user_id);
				if(array_key_exists('rating', $player))
				{
					$template->assign_var('S_EDIT_PLAYER', true);

					$template->assign_var('USERNAME', $player['username']);
					$template->assign_var('PLAYER_RATING', $player['rating']);
				}
				else
				{
					$template->assign_var('S_NEW_PLAYER', true);
				}
			}
		}

		if($username != '')
		{
			$template->assign_var('S_PLAYER_NOT_FOUND', true);
		}
		$template->assign_var('S_SELECT_PLAYER', true);
		$template->assign_var('U_FIND_USERNAME', append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=mcp&amp;field=username&amp;select_single=true'));
	}

	function civs()
	{
		global $template, $request;
		global $zone_civs;

		$template->assign_var('S_MANAGE_CIVS', true);

		$civ_id = $request->variable('civ_id', '');

		$create_civ = $request->variable('create_civ', '');
		$edit_civ = $request->variable('edit_civ', '');
		if($create_civ)
		{
				$civ_name = $request->variable('civ_name', '');
				$zone_civs->create_civ($civ_name);
		}
		elseif($edit_civ)
		{
				$civ_name = $request->variable('civ_name', '');
				$zone_civs->edit_civ($civ_id, array('name' => $civ_name));
				$civ_id = ''; // back to selection page
		}

		if($civ_id == '') // no civ selected
		{
			$template->assign_var('S_SELECT_CIV', true);

			$civs = $zone_civs->get_civs();
			if(count($civs))
			{
				$civs_block = array();
				foreach($civs as $civ)
				{
					$template->assign_block_vars('civs', array(
						'ID' => (int) $civ['id'],
						'NAME' => $civ['name']
					));
				}
			}
		}
		elseif($civ_id == 0) // new civ
		{
			$template->assign_var('S_NEW_CIV', true);
		}
		else // civ selected
		{
			$template->assign_var('S_EDIT_CIV', true);

			$civ = $zone_civs->get_civ($civ_id);
			$template->assign_var('S_CIV_ID', $civ_id);
			$template->assign_var('S_CIV_NAME', $civ['name']);
		}
	}

	function maps()
	{
		global $template, $request, $auth;
		global $zone_maps, $zone_civs;

		$template->assign_var('S_MANAGE_MAPS', true);
		$template->assign_var('S_CAN_CREATE_MAP', $auth->acl_get('m_zone_create_maps'));

		$map_id = $request->variable('map_id', '');

		$create_map = $request->variable('create_map', '');
		$edit_map = $request->variable('edit_map', '');
		if($create_map && $auth->acl_get('m_zone_create_maps'))
		{
			$map_name = $request->variable('map_name', '');
			$map_weight = $request->variable('map_weight', 0.0);
			$copy_map_id = $request->variable('copy_map_id', 0);
			$zone_maps->create_map($map_name, $map_weight, $copy_map_id);
		}
		elseif($edit_map)
		{
			$map_name = $request->variable('map_name', '');
			$map_weight = $request->variable('map_weight', 0.0);
			$zone_maps->edit_map($map_id, array(
				'name' => $map_name,
				'weight' => $map_weight)
			);

			$civs = $zone_civs->get_civs();
			$civ_info = array();
			foreach($civs as $civ)
			{
				$civ_id = $civ['id'];

				$multiplier = $request->variable('multiplier_' . $civ_id, '');
				$force_draw = $request->variable('force_draw_' . $civ_id, '');
				$prevent_draw = $request->variable('prevent_draw_' . $civ_id, '');
				$both_teams = $request->variable('both_teams_' . $civ_id, '');

				$civ_info[$civ_id] = array(
					'multiplier' => $multiplier,
					'force_draw' => ($force_draw == 'on'),
					'prevent_draw' => ($prevent_draw == 'on'),
					'both_teams' => ($both_teams == 'on')
				);
			}
			$zone_maps->edit_map_civs($map_id, $civ_info);

			$map_id = '';
		}

		if($map_id == '')
		{
			$template->assign_var('S_SELECT_MAP', true);

			$maps = $zone_maps->get_maps();
			if(count($maps))
			{
				$maps_block = array();
				foreach($maps as $map)
				{
					$template->assign_block_vars('maps', array(
						'ID' => (int) $map['id'],
						'NAME' => $map['name']
					));
				}
			}
		}
		elseif($map_id == 0)
		{
			$template->assign_var('S_NEW_MAP', true);

			$maps = $zone_maps->get_maps();
			if(count($maps))
			{
				$maps_block = array();
				foreach($maps as $map)
				{
					$template->assign_block_vars('maps', array(
						'ID' => (int) $map['id'],
						'NAME' => $map['name']
					));
				}
			}
		}
		else
		{
			$template->assign_var('S_EDIT_MAP', true);

			$map = $zone_maps->get_map($map_id);
			$template->assign_var('S_MAP_ID', $map_id);
			$template->assign_var('S_MAP_NAME', $map['name']);
			$template->assign_var('S_MAP_WEIGHT', $map['weight']);

			$map_civs = $zone_maps->get_map_civs($map_id);
			foreach($map_civs as $map_civ)
			{
				$civ = $zone_civs->get_civ($map_civ['civ_id']);

				$template->assign_block_vars('map_civs', array(
					'CIV_ID' => $map_civ['civ_id'],
					'CIV_NAME' => $civ['name'],
					'MULTIPLIER' => $map_civ['multiplier'],
					'FORCE_DRAW' => $map_civ['force_draw'] ? 'checked' : '',
					'PREVENT_DRAW' => $map_civ['prevent_draw'] ? 'checked' : '',
					'BOTH_TEAMS' => $map_civ['both_teams'] ? 'checked' : ''
				));
			}
		}
	}
}