<?php
$thisPage = "admin";
require_once 'common.php';
include 'header.php';

$templateName = 'tslog';
$pagePermissions = array("webReg_Valid", "webReg_AdminPanel");


	$sql = 'SELECT t.* FROM log_ts as t
			INNER JOIN (
				SELECT client_nickname, count(ts_log_id) as name_cnt FROM log_ts GROUP BY client_nickname HAVING name_cnt >1
			) as found ON t.client_nickname = found.client_nickname
			ORDER BY t.connection_time DESC';

	$toTemplate['ts_by_name'] = CleanUnChangedByName( SafeQuery( $sql ) );


	$sql = 'SELECT t.* FROM log_ts as t
				INNER JOIN (
					SELECT client_unique_identifier, count(ts_log_id) as uniq_cnt FROM log_ts GROUP BY client_unique_identifier HAVING uniq_cnt >1
				) as found ON t.client_unique_identifier = found.client_unique_identifier
				ORDER BY t.connection_time DESC';

	$toTemplate['ts_by_uniq'] = CleanUnChangedByUniq( SafeQuery( $sql ) );

	require 'twigRender.php';


	function CleanUnChangedByName( $list) {
     	if( !$list ) return;

		$ips = null;
		$uniqs = null;	

		for( $i = count( $list ); $i>=0; $i-- ) {
			$name = $list[ $i ]['client_nickname'];
			$ip = $list[ $i ]['connection_client_ip'];
			$uniq = $list[ $i ]['client_unique_identifier'];

			if( isset( $ips[$name] ) && $ips[$name] === $ip ) {
				$list[ $i ]['connection_client_ip'] = '';
				$list[ $i ]['connection_country'] = '';
			} else {
				$ips[$name] = $ip;
			}

			if( isset( $uniqs[$name] ) && $uniqs[$name] === $uniq ) {
				$list[ $i ]['client_unique_identifier'] = '';
			} else {
				$uniqs[$name] = $uniq;
			}
		}

		return $list;
	}


	function CleanUnChangedByUniq( $list) {
     	if( !$list ) return;

		$ips = null;
		$names = null;	

		for( $i = count( $list ); $i>=0; $i-- ) {
			$name = $list[ $i ]['client_nickname'];
			$ip = $list[ $i ]['connection_client_ip'];
			$uniq = $list[ $i ]['client_unique_identifier'];

			if( isset( $ips[$uniq] ) && $ips[$uniq] === $ip ) {
				$list[ $i ]['connection_client_ip'] = '';
				$list[ $i ]['connection_country'] = '';
			} else {
				$ips[$uniq] = $ip;
			}

			if( isset( $names[$uniq] ) && $names[$uniq] === $name ) {
				$list[ $i ]['client_nickname'] = '';
			} else {
				$names[$uniq] = $name;
			}
		}

		return $list;
	}




	function SafeQuery( $sql, $params = null ) {
        $db = db::getInstance();

        if( $params ) {
            foreach( $params as $name => $value ) {
                $safe_value = null;

                if( null === $value ) {
                    $safe_value = 'NULL';
                } else {
                    if( is_numeric( $value ) ) {
                        $safe_value = 0+$value;
                    } else {
                        $safe_value = "'" . $db->sanitizeString( $value ) . "'";
                    }
                }

                $sql = str_replace( '{' . $name . '}', $safe_value, $sql );
            }
        }

        $result = $db->query( $sql );
        $data = null;

        if( gettype($result) == "object" ) {
            $data = $db->fetchAssoc( $result );
        }

        return $data;
    }

