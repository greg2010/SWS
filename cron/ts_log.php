<?php
/**
	Логирует все уникальные сочитания уник, ник, ip подключенных клиентов TeamSpeak

	Результаты хранятся в таблице:

	CREATE TABLE  `log_ts` (
	  `ts_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `client_nickname` varchar(128) NOT NULL,
	  `client_unique_identifier` varchar(128) CHARACTER SET ascii NOT NULL,
	  `client_country` char(2) CHARACTER SET ascii NOT NULL,
	  `connection_client_ip` varchar(32) CHARACTER SET ascii NOT NULL,
	  `connection_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	  PRIMARY KEY (`ts_log_id`),
	  UNIQUE KEY `ts_log_uniq` (`client_nickname`,`client_unique_identifier`,`connection_client_ip`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8
*/


	require_once dirname(__FILE__) . '/../init.php';

	try {
		$ts3 = new ts3();
		$ts_admin = $ts3->connectTs();

		$servers = $ts_admin->serverList();
		CheckReply( $servers, 'ERR_TS_CONNECT' );

		foreach( $servers['data'] as $server ) {
         	LogServerClients( $server, $ts_admin );
		}

		echo "\n[BYE]\n";

	} catch ( Exception $ex ) {
		echo "\n[ERROR] " . $ex->getMessage();
	}


	function CheckReply( $reply, $error ) {
		if( isset( $reply['success'] ) && $reply['success'] ) {
			return;
		}

		throw new Exception( $error );
	}


	function LogServerClients( $server, $ts_admin ) {
		if( 'online' !== $server['virtualserver_status'] ) {
        	return;
		}

		$result = $ts_admin->selectServer( $server['virtualserver_port'] );
		CheckReply( $result, 'ERR_TS_CONNECT_VIRTUAL' );
		
		$clients = $ts_admin->clientList( '-uid -country -ip' );
		CheckReply( $clients, 'ERR_TS_CLIENTS_LIST' );

		foreach( $clients['data'] as $client ) {
        	LogClient( $client );
		}
	}


	function LogClient( $client ) {
		if( 1 == $client['client_type'] ) {
			// Do not log admins
         	return;
		}


		$client['client_nickname'] = $client['client_nickname'];

		$sql = 'SELECT ts_log_id 
					FROM log_ts
					WHERE client_nickname = {client_nickname} 
						AND client_unique_identifier = {client_unique_identifier} 
						AND connection_client_ip = {connection_client_ip}';

		$result = SafeQuery( $sql, $client );
		if( !$result ) {
			$sql = 'INSERT INTO log_ts ( client_nickname, client_unique_identifier, client_country, connection_client_ip, connection_time )
						VALUES( {client_nickname}, {client_unique_identifier}, {client_country}, {connection_client_ip}, now() )';
			SafeQuery( $sql, $client );
		}
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
