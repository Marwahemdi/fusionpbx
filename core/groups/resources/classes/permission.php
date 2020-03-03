<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2013-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the permission class
	class permission {

		//delete the permissions
			function delete() {

				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}

				//initialize array
					$group_name_array = array();

				//restore default permissions
					$x = 0;
					foreach ($apps as $row) {
						if (is_array($row['permissions']) && @sizeof($row['permissions']) != 0) {
							foreach ($row['permissions'] as $permission) {
								if (is_array($permission['groups'])) {
									foreach ($permission['groups'] as $group_name) {
										if (is_array($group_name_array) || !in_array($group_name, $group_name_array)) {
											$group_name_array[] = $group_name;
										}
									}
								}
							}
						}
					}
					$group_names = "'".implode("','", $group_name_array)."'";

				//delete unprotected system group permissions
					$sql = "delete from v_group_permissions as p ";
					$sql .= "where group_name in ( ";
					$sql .= "	select group_name ";
					$sql .= "	from v_groups ";
					$sql .= "	where group_protected <> 'true' ";
					$sql .= "	and group_name in (".$group_names.") ";
					$sql .= ");";
					$database = new database;
					$result = $database->select($sql);

				//get the group_permissons
					/*
					$sql = "select * from v_group_permissions as p ";
					$sql .= "where group_name in ( ";
					$sql .= "	select group_name ";
					$sql .= "	from v_groups ";
					$sql .= "	where group_protected <> 'true' ";
					$sql .= "	and group_name in (".$group_names.") ";
					$sql .= ");";
					$database = new database;
					$group_permissions = $database->select($sql, null, 'all');
					*/

				//delete unprotected group permissions
					/*
					if (is_array($group_permissions) && sizeof($group_permissions) > 0) {
						$x = 0;
						foreach ($group_permissions as $row) {
							//build delete array
								$array['group_permissions'][$x]['group_permission_uuid'] = $row['group_permission_uuid'];
								$array['group_permissions'][$x]['domain_uuid'] = ($row['domain_uuid'] != '') ? $row['domain_uuid'] : null;
								$x++;
						}
						if (is_array($array) && @sizeof($array) != 0) {
							//grant temporary permissions
								$p = new permissions;
								$p->add('group_permission_delete', 'temp');
							//execute delete
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->delete($array);
								unset($array);
							//revoke temporary permissions
								$p->delete('group_permission_delete', 'temp');
						}
					}
					*/
			}

		//restore the permissions
			function restore() {

				//if the are no groups add the default groups
					$sql = "select * from v_groups ";
					$sql .= "where domain_uuid is null ";
					$database = new database;
					$groups = $database->select($sql, null, 'all');

				//delete the group permissions
					$this->delete();

				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}

				//restore default permissions
					$x = 0;
					foreach ($apps as $row) {
						if (is_array($row['permissions']) && @sizeof($row['permissions']) != 0) {
							foreach ($row['permissions'] as $permission) {
								//set the variables
								if ($permission['groups']) {
									foreach ($permission['groups'] as $group_name) {
										//check group protection
										if (is_array($groups)) {
											foreach ($groups as $group) {
												if ($group['group_name'] == $group_name) {
													$group_uuid = $group['group_uuid'];
													$group_protected = $group['group_protected'] == 'true' ? true : false;
													break;
												}
											}
										}

										if (!$group_protected) {
											//if the item uuid is not currently in the db then add it
											$sql = "select count(*) from v_group_permissions ";
											$sql .= "where permission_name = :permission_name ";
											$sql .= "and group_name = :group_name ";
											$parameters['permission_name'] = $permission['name'];
											$parameters['group_name'] = $group_name;

											$database = new database;
											$num_rows = $database->select($sql, $parameters, 'column');
											unset($sql, $parameters);

											if ($num_rows == 0) {
												//build default permissions insert array
												$array['group_permissions'][$x]['group_permission_uuid'] = uuid();
												$array['group_permissions'][$x]['permission_name'] = $permission['name'];
												$array['group_permissions'][$x]['group_name'] = $group_name;
												$array['group_permissions'][$x]['group_uuid'] = $group_uuid;
												$x++;
											}
										}
									}
								}
							}
						}
					}

					if (is_array($array) && @sizeof($array)) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('group_permission_add', 'temp');

						//execute insert
							$database = new database;
							$database->app_name = 'groups';
							$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
							$database->save($array);
							unset($array);

						//revoke temporary permissions
							$p->delete('group_permission_add', 'temp');
					}

			}

	}

?>