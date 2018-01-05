<?php
class ImportWHMCS {
	public $settings = array(
		'name' => 'Import WHMCS',
		'admin_menu_category' => 'Settings',
		'admin_menu_name' => 'Import WHMCS',
		'admin_menu_icon' => '<i class="icon-random"></i>',
		'description' => 'Imports data from a WHMCS installation.',
	);
	function admin_area() {
		global $billic, $db;
		//set_title('Admin/Import WHMCS');
		echo '<h1>Import WHMCS</h1>';
		if (get_config('Import_WHMCS_Host') == '') {
			err('You need to configure the WHMCS database information first');
		}
		switch ($_POST['action']) {
			case 'import_users':
				echo 'Connecting to WHMCS database... ';
				$link = mysqli_connect(get_config('Import_WHMCS_Host') , get_config('Import_WHMCS_User') , get_config('Import_WHMCS_Pass') , get_config('Import_WHMCS_Name')) or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				echo 'Getting the list of clients... ';
				$result = $link->query('SELECT * FROM `tblclients`') or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				while ($row = mysqli_fetch_array($result)) {
					echo 'Importing ' . $row['email'] . '... ';
					$exists = $db->q('SELECT COUNT(*) FROM `users` WHERE `email` = ?', $row['email']);
					if ($exists[0]['COUNT(*)'] > 0) {
						echo '<span class="blue">Skipped (user exists)</span><br>';
						continue;
					}
					$date = $row['datecreated'];
					$date = explode('-', $date);
					$datecreated = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$db->insert('users', array(
						'firstname' => $row['firstname'],
						'lastname' => $row['lastname'],
						'companyname' => $row['companyname'],
						'email' => $row['email'],
						'address1' => $row['address1'],
						'address2' => $row['address2'],
						'city' => $row['city'],
						'state' => $row['state'],
						'postcode' => $row['postcode'],
						'country' => $row['country'],
						'phonenumber' => $row['phonenumber'],
						'password' => $row['password'],
						'credit' => $row['credit'],
						'datecreated' => $datecreated,
						'notes' => $row['notes'],
						'registered_ip' => $row['ip'],
						'registered_host' => $row['host'],
						'status' => $row['status'],
						'emailoptout' => $row['emailoptout'],
					));
					echo '<span class="green">OK</span><br>';
				}
			break;
			case 'import_services':
				echo 'Connecting to WHMCS database... ';
				$link = mysqli_connect(get_config('Import_WHMCS_Host') , get_config('Import_WHMCS_User') , get_config('Import_WHMCS_Pass') , get_config('Import_WHMCS_Name')) or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				echo 'Getting the list of services... ';
				$result = $link->query('SELECT * FROM `tblhosting`') or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				while ($row = mysqli_fetch_array($result)) {
					echo 'Importing ' . $row['domain'] . '... ';
					$exists = $db->q('SELECT COUNT(*) FROM `services` WHERE `domain` = ? AND `username` = ?', $row['domain'], $row['username']);
					if ($exists[0]['COUNT(*)'] > 0) {
						echo '<span class="blue">Skipped (service domain+username exists)</span><br>';
						continue;
					}
					$billic->useremail = $link->query('SELECT `email` FROM `tblclients` WHERE `id` = ' . $row['userid']) or err(mysqli_error($link));
					$billic->useremail = mysqli_fetch_array($billic->useremail);
					$billic->useremail = $billic->useremail['email'];
					$billic->userid = $db->q('SELECT `id` FROM `users` WHERE `email` = ?', $billic->useremail);
					$billic->userid = $billic->userid[0]['id'];
					if (empty($billic->userid)) {
						echo '<span class="blue">Skipped (need to import client)</span><br>';
						continue;
					}
					$date = $row['nextduedate'];
					$date = explode('-', $date);
					$nextduedate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$date = $row['regdate'];
					$date = explode('-', $date);
					$regdate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$db->insert('services', array(
						'userid' => $billic->userid,
						'regdate' => $regdate,
						'domain' => $row['domain'],
						'amount' => $row['amount'],
						'billingcycle' => $row['billingcycle'],
						'nextduedate' => $nextduedate,
						'domainstatus' => $row['domainstatus'],
						'username' => $row['username'],
						'notes' => $row['notes'],
						'ipaddresses' => $row['assignedips'],
					));
					echo '<span class="green">OK</span><br>';
				}
			break;
			case 'import_invoices':
				echo 'Connecting to WHMCS database... ';
				$link = mysqli_connect(get_config('Import_WHMCS_Host') , get_config('Import_WHMCS_User') , get_config('Import_WHMCS_Pass') , get_config('Import_WHMCS_Name')) or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				echo 'Getting the list of invoices... ';
				$result = $link->query('SELECT * FROM `tblinvoices` WHERE `status` != \'Unpaid\'') or err(mysqli_error($link));
				echo '<span class="green">OK</span><br>';
				while ($row = mysqli_fetch_array($result)) {
					echo 'Importing Invoice #' . $row['id'] . '... ';
					$exists = $db->q('SELECT COUNT(*) FROM `invoices` WHERE `id` = ?', $row['id']);
					if ($exists[0]['COUNT(*)'] > 0) {
						echo '<span class="blue">Skipped (invoice ID exists)</span><br>';
						continue;
					}
					$billic->useremail = $link->query('SELECT `email` FROM `tblclients` WHERE `id` = ' . $row['userid']) or err(mysqli_error($link));
					$billic->useremail = mysqli_fetch_array($billic->useremail);
					$billic->useremail = $billic->useremail['email'];
					$billic->userid = $db->q('SELECT `id` FROM `users` WHERE `email` = ?', $billic->useremail);
					$billic->userid = $billic->userid[0]['id'];
					if (empty($billic->userid)) {
						echo '<span class="blue">Skipped (need to import client)</span><br>';
						continue;
					}
					$date = $row['date'];
					$date = explode('-', $date);
					$date = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$date = $row['duedate'];
					$date = explode('-', $date);
					$duedate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$date = $row['datepaid'];
					$date = explode('-', $date);
					$datepaid = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
					$db->insert('invoices', array(
						'id' => $row['id'],
						'userid' => $billic->userid,
						'date' => $date,
						'duedate' => $duedate,
						'datepaid' => $datepaid,
						'subtotal' => $row['subtotal'],
						'credit' => $row['credit'],
						'tax' => ($row['tax'] + $row['tax2']) ,
						'total' => $row['total'],
						'taxrate' => ($row['taxrate'] + $row['taxrate2']) ,
						'status' => $row['status'],
					));
					echo '<span class="green">OK</span><br>';
					echo 'Importing items... ';
					$itemresult = $link->query('SELECT * FROM `tblinvoiceitems` WHERE `invoiceid` = ' . $row['id']) or err(mysqli_error($link));
					while ($itemrow = mysqli_fetch_array($itemresult)) {
						$db->insert('invoiceitems', array(
							'invoiceid' => $row['id'],
							'type' => $itemrow['type'],
							'description' => $itemrow['description'],
							'amount' => $itemrow['amount'],
						));
					}
					echo '<span class="green">OK</span><br>';
				}
			break;
			default:
				echo '<div style="padding: 20px"><form method="POST">';
				echo '<input type="radio" name="action" value="import_users"> Import Clients<br>';
				echo '<input type="radio" name="action" value="import_services"> Import Services<br>';
				echo '<input type="radio" name="action" value="import_invoices"> Import Invoices (will not import unpaid invoices because Billic will generate new invoices for any due services)<br>';
				echo '<input type="submit" class="btn btn-default" value="Start Import &raquo;">';
				echo '</form></div>';
			break;
		}
	}
	function settings($array) {
		global $billic, $db;
		if (empty($_POST['update'])) {
			echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="ImportWHMCS"><table class="table table-striped">';
			echo '<tr><th colspan="2">WHMCS Database Settings</th></tr>';
			echo '<tr><td>Host</td><td><input type="text" class="form-control" name="Import_WHMCS_Host" value="' . safe(get_config('Import_WHMCS_Host')) . '"></td></tr>';
			echo '<tr><td>User</td><td><input type="text" class="form-control" name="Import_WHMCS_User" value="' . safe(get_config('Import_WHMCS_User')) . '"></td></tr>';
			echo '<tr><td>Pass</td><td><input type="text" class="form-control" name="Import_WHMCS_Pass" value="' . safe(get_config('Import_WHMCS_Pass')) . '"></td></tr>';
			echo '<tr><td>Name</td><td><input type="text" class="form-control" name="Import_WHMCS_Name" value="' . safe(get_config('Import_WHMCS_Name')) . '"></td></tr>';
			echo '<tr><td colspan="2" align="center"><input type="submit" class="btn btn-default" name="update" value="Update &raquo;"></td></tr>';
			echo '</table></form>';
		} else {
			if (empty($billic->errors)) {
				set_config('Import_WHMCS_Host', $_POST['Import_WHMCS_Host']);
				set_config('Import_WHMCS_User', $_POST['Import_WHMCS_User']);
				set_config('Import_WHMCS_Pass', $_POST['Import_WHMCS_Pass']);
				set_config('Import_WHMCS_Name', $_POST['Import_WHMCS_Name']);
				$billic->status = 'updated';
			}
		}
	}
}
