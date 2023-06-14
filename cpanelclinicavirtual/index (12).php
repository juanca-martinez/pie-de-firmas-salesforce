<?php

session_start();
require './vendor/autoload.php';
require './vendor/phpmailer/phpmailer/class.phpmailer.php';
require './vendor/phpmailer/phpmailer/class.smtp.php';
include './cms/inc/config.php';
include './cms/inc/lang.php';
include './cms/inc/functions.php';

$conn = getDbConnection($config);

Twig_Autoloader::register();


$loader = new Twig_Loader_Filesystem('.');
// $twig = new Twig_Environment($loader, array('charset' => 'iso-8859-1'));
$twig = new Twig_Environment($loader, array('charset' => 'iso-8859-1'));

$render = array();

$loadLanding = $loadCharla = false;
if ($_GET['slug']=="vidasecuritybhp"){
		header("Location: https://www.clinicavirtual.com/vidasecuritygold");
		exit();
	}
if ($_GET['slug']=="itau"){
		header("Location: https://www.clinicavirtual.com/vidasecuritygold");
		exit();
	}
// slug es el nombre de la empresa del landing

if ($_GET['slug'])
{
	$query = $conn->prepare("select * from {$config['prefix']}empresas where status = '1' and slug = :slug");
	$query->execute(array('slug' => $_GET['slug']));
	$res = $query->fetchAll();
	if (count($res)) 
	{

		$loadLanding = true;
		$render['year'] = date("Y");

		foreach ($res as $row) 
		{
			$row = xmlFormat($row);

			if ($_POST['submitSuscripcion']) 
			{

					// formulario
				unset($frmError);
				unset($validate);

				$validate[] = 'suscripcionNombre';
			    $validate[] = 'suscripcionEmail';
			    $validate[] = 'suscripcionEmpresa';
			    $validate[] = 'suscripcionPais';

				foreach ($validate as $v) 
				{
					if (trim($_POST[$v]) == '') 
					{
						$frmError = $language['translationCompleteFields'][$config['lang']];
					}
				}

				if (!$frmError) 
				{
					if (!checkMail($_POST['suscripcionEmail'])) 
					{
						$frmError = $language['translationCompleteEmail'][$config['lang']];
					} 
					else 
					{

			            unset($fields);
						$fields['fecha'] = date("Y-m-d H:i:s");
						$fields['ip'] = getIp();
						$fields['idEmpresa'] = $row['id'];
						$fields['nombreEmpresa'] = $row['titulo'];
						$fields['frmNombre'] = $_POST['suscripcionNombre'];
						$fields['frmEmail'] = $_POST['suscripcionEmail'];
						$fields['frmEmpresa'] = $_POST['suscripcionEmpresa'];
						$fields['frmPais'] = $_POST['suscripcionPais'];

						if (is_array($_POST['subscripcionItems']))
						{
							$fields['frmSuscripcion'] = implode(', ', $_POST['subscripcionItems']);
						}

						foreach ($_POST as $c => $v) 
						{
							if (!is_array($v))
							{
								$_POST[$c] = stripslashes(htmlspecialchars($v, ENT_QUOTES));
							}
						}

						$content = <<<EOD
						<b>Empresa:</b> {$fields['nombreEmpresa']} <br />
						<hr />
						<b>Nombre y Apellido:</b> {$fields['frmNombre']} <br />
						<b>Email:</b> {$fields['frmEmail']} <br />
						<b>Empresa:</b> {$fields['frmEmpresa']} <br />
						<b>Pais:</b> {$fields['frmPais']} <br />
						<b>Suscripciones:</b> {$fields['frmSuscripcion']} <br />
						EOD;

						$fields['html'] = $content;

						$stmt = $conn->prepare("insert into {$config['prefix']}suscripcion (".implode(', ', array_keys($fields)).") values (".prepareFields($fields, 'insert').")");
						$stmt->execute(prepareFieldsArray($fields));

						unset($params);
						$params['fromMail'][$config['email_address']] = $config['siteName'];
						$params['subject'] = $config['siteName'].' - Suscripcion';
						$params['toMail'][$config['email_address']] = $config['siteName'];
						$params['replyMail'][$fields['frmEmail']] = stripslashes($fields['frmNombre']);
						$params['content'] = utf8_decode($content);

						if (sendEmail($config, $params)) 
						{
							echo 'ok';
							exit;
						} 
						else 
						{
							$frmError = $language['translationErrorSendingEmail'][$config['lang']];
						}
			        }
				}

				if ($frmError != '')
				{
					echo $frmError;
				}

				exit;
			}

			if ($_POST['submitContacto']) 
			{

				// formulario
				unset($frmError);
				unset($validate);

				$validate[] = 'contactoNombre';
			    $validate[] = 'contactoEmail';
			    // $validate[] = 'contactoEmpresa';
			    $validate[] = 'contactoPais';
			    $validate[] = 'contactoAsunto';
			    $validate[] = 'contactoTelefono';
			        // $validate[] = 'contactoUsuario';

				foreach ($validate as $v) 
				{
					if (trim($_POST[$v]) == '') 
					{
						$frmError = $language['translationCompleteFields'][$config['lang']];
					}
				}

			    // $validate[] = 'contactoTelefono';
			    $validate[] = 'contactoMensaje';

				if (!$frmError) 
				{

					if (!checkMail($_POST['contactoEmail'])) 
					{
						$frmError = $language['translationCompleteEmail'][$config['lang']];
					} 
					else 
					{

			            unset($fields);
						$fields['fecha'] = date("Y-m-d H:i:s");
						$fields['ip'] = getIp();
						$fields['idEmpresa'] = $row['id'];
						$fields['nombreEmpresa'] = $row['titulo'];
						$fields['frmNombre'] = $_POST['contactoNombre'];
						$fields['frmEmail'] = $_POST['contactoEmail'];
						// $fields['frmEmpresa'] = $_POST['contactoEmpresa'];
						$fields['frmEmpresa'] = $row['slug'];
						$fields['frmPais'] = $_POST['contactoPais'];
						$fields['asunto'] = $_POST['contactoAsunto'];
						// $fields['frmUsuario'] = $_POST['contactoUsuario'];
						$fields['frmTelefono'] = $_POST['contactoTelefono'];
						$fields['frmMensaje'] = $_POST['contactoMensaje'];

						foreach ($_POST as $c => $v) 
						{
							if (!is_array($v))
								$_POST[$c] = stripslashes(htmlspecialchars($v, ENT_QUOTES));
						}

                		$_POST['frmMensaje'] = nl2br($_POST['frmMensaje']);


                		$nombre_asunto = '';

                		if($fields['asunto'] == 1)
                		{
							$nombre_asunto = 'Sugerencias';
                		}
                		else if ($fields['asunto'] == 2)
                		{
                			$nombre_asunto = 'Felicitaciones';
                		}
                		else if($fields['asunto'] == 3)
                		{
                			$nombre_asunto = 'Consultas';
                		}



						$content = <<<EOD
						<b>Empresa:</b> {$fields['nombreEmpresa']} <br />
						<hr />

						<b>Nombre y Apellido:</b> {$fields['frmNombre']} <br />
						<b>Asunto:</b> {$nombre_asunto} <br />
						<b>Email:</b> {$fields['frmEmail']} <br />
						<b>Telefono:</b> {$fields['frmTelefono']} <br />
						<b>Empresa:</b> {$fields['frmEmpresa']} <br />
						<b>Pais:</b> {$fields['frmPais']} <br />
						<b>Mensaje:</b><br /> {$fields['frmMensaje']} <br />
						
						EOD;

						$fields['html'] = $content;

						$stmt = $conn->prepare("insert into {$config['prefix']}contacto (".implode(', ', array_keys($fields)).") values (".prepareFields($fields, 'insert').")");
						$stmt->execute(prepareFieldsArray($fields));

						unset($params);
						$params['fromMail'][$config['email_address']] = $config['siteName'];
						$params['subject'] = $config['siteName'].' - Contacto';
						// $params['toMail'][$config['email_address']] = $config['siteName'];
						$params['toMail'][$row['emailContacto']] = $config['siteName'];
						$params['replyMail'][$fields['frmEmail']] = stripslashes($fields['frmNombre']);
						$params['content'] = utf8_decode($content);


						if (sendEmail($config, $params)) 
						// if (sendEmailContacto($config, $params)) 
						{
							echo 'ok';
							exit;
						} 
						else 
						{
							$frmError = $language['translationErrorSendingEmail'][$config['lang']];
						}
			        }
				}

				if ($frmError != '')
				{
					echo $frmError;
				}

				exit;
			}

			$render['link'] = getLinkEmpresa($config, $row['slug']);

			if ($row['analytics']) 
			{
            	$render['analytics'] = $row['analytics'];
			}

			if (trim($row['btnIniciarVideollamada']) != '') 
			{
            	$render['btnIniciarVideollamada'] = $row['btnIniciarVideollamada'];
			}
				
            $render['id'] = $row['id'];
            $render['titulo'] = $row['titulo'];
       
            $render['tituloUrl'] = urlencode($row['titulo']);
            $render['logo'] = $config['site_url'].'/files/empresas/logos/'.$row['logo'];
            // $render['header'] = $config['site_url'].'/files/empresas/headers/thumbs/'.$row['imagenHeader'];
            // $render['headerMobile'] = $config['site_url'].'/files/empresas/headers/thumbs/'.$row['imagenHeaderMobile'];

            $render['emailContacto'] = $row['emailContacto'];
            $render['telefonoContacto'] = $row['telefonoContacto'];
            $render['telefonoContacto_n'] = onlyDigits($row['telefonoContacto']);
            $render['tiempoSlider'] = $row['tiempoSlider'];
            $render['imagenPlataformaAtencion'] = $config['site_url'].'/files/empresas/headers/thumbs/'.$row['imagenPlataformaAtencion'];
            $render['legales'] = $row['legales'];

            if (trim($row['chat']) != '') 
            {
            	$render['chat'] = $row['chat'];
            }

            // slider
            $itemsSlider = array();
			$querySlider = $conn->prepare("select * from {$config['prefix']}slider where idEmpresa = '{$row['id']}' and status = '1' order by position");
			$querySlider->execute();
			$resSlider = $querySlider->fetchAll();
			if (count($resSlider)) 
			{
				foreach ($resSlider as $rowSlider) 
				{
					$rowSlider = xmlFormat($rowSlider);

					if (trim($rowSlider['link']) != '')
					{
						$itemsSlider[$rowSlider['id']]['link'] = $rowSlider['link'];
					}
					
					$itemsSlider[$rowSlider['id']]['header'] = $config['site_url'].'/files/empresas/headers/thumbs/'.$rowSlider['image'];
					$itemsSlider[$rowSlider['id']]['headerMobile'] = $config['site_url'].'/files/empresas/headers/thumbs/'.$rowSlider['imageMobile'];
				}

				$render['slider'] = $itemsSlider;
			}

			$arrayPais = xmlFormat(getArray($config, 'id', "{$config['prefix']}paises", $row['idPais']));
            $render['tyc'] = $arrayPais['tyc'];

            // fechas videllamadas reservadas
            $itemsVideollamadas = array();
			$queryVideollamadas = $conn->prepare("select * from {$config['prefix']}videollamadas_citas where idEmpresa = '{$row['id']}'");
			$queryVideollamadas->execute();
			$resVideollamadas = $queryVideollamadas->fetchAll();
			if (count($resVideollamadas)) 
			{
				foreach ($resVideollamadas as $rowVideollamadas) 
				{
					$rowVideollamadas = xmlFormat($rowVideollamadas);
					$itemsVideollamadas[$rowVideollamadas['idPrograma']][date("Y-m-d", strtotime($rowVideollamadas['frmFecha']))][] = date("H:i", strtotime($rowVideollamadas['frmFecha']));
				}
			}

			// printArray($itemsVideollamadas);

			$counterProgramasAll = 1;
			$counterProgramas = 0;
			unset($itemsProgramas2);
			unset($itemsProgramas2ok);
			unset($itemsProgramasModal2);
			unset($aDaysVideo);
			unset($itemsInformaciones);
            $itemsProgramas = array();
			$queryProgramas = $conn->prepare("select * from {$config['prefix']}rel_empresas_programas where idEmpresa = '{$row['id']}'");
			$queryProgramas->execute();
			$resProgramas = $queryProgramas->fetchAll();
			if (count($resProgramas)) 
			{
				foreach ($resProgramas as $rowProgramas) 
				{
					$rowProgramas = xmlFormat($rowProgramas);

					$counterProgramas++;

					if ($counterProgramas > 12)
					{
						$counterProgramas = 1;
					}

					$arrayProgramaTmp = xmlFormat(getArray($config, 'id', "{$config['prefix']}programas", $rowProgramas['idPrograma']));

					$itemsProgramas2ok[$rowProgramas['idPrograma']]['id'] = $rowProgramas['idPrograma'];
					$itemsProgramas2ok[$rowProgramas['idPrograma']]['descripcion'] = $rowProgramas['tituloPrograma'];
					$itemsProgramas2ok[$rowProgramas['idPrograma']]['descripcion_previo'] = $rowProgramas['tituloPrevio'];
					$itemsProgramas2ok[$rowProgramas['idPrograma']]['imagen'] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['imagen'];
					$itemsProgramas2ok[$rowProgramas['idPrograma']]['logo'] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['icono'];
						
					$itemsProgramas2[$counterProgramasAll]['id_'.$counterProgramas] = $rowProgramas['idPrograma'];
					$itemsProgramas2[$counterProgramasAll]['descripcion_'.$counterProgramas] = $rowProgramas['tituloPrograma'];
					$itemsProgramas2[$counterProgramasAll]['descripcion_previo_'.$counterProgramas] = $rowProgramas['tituloPrevio'];

					$itemsProgramas2[$counterProgramasAll]['imagen_'.$counterProgramas] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['imagen'];
					$itemsProgramas2[$counterProgramasAll]['logo_'.$counterProgramas] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['icono'];

					/*
					if ($rowProgramas['idPrograma'] == 1)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'telemedicina';
					elseif ($rowProgramas['idPrograma'] == 2)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'orienta';
					elseif ($rowProgramas['idPrograma'] == 3)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'psicologia';
					elseif ($rowProgramas['idPrograma'] == 4)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'nutricion';
					elseif ($rowProgramas['idPrograma'] == 5)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'deporte';
					elseif ($rowProgramas['idPrograma'] == 6)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'sueno';
					elseif ($rowProgramas['idPrograma'] == 7)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'cronico';
					elseif ($rowProgramas['idPrograma'] == 9)
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = 'cronico';
					*/

					if (trim($arrayProgramaTmp['variableChat']) != '')
					{
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['AsisteClickLaunch'] = $arrayProgramaTmp['variableChat'];
					}

					$itemsProgramasModal2[$rowProgramas['idPrograma']]['id'] = $rowProgramas['idPrograma'];
					$itemsProgramasModal2[$rowProgramas['idPrograma']]['tituloCustom'] = $arrayProgramaTmp['tituloCustom'];
					$itemsProgramasModal2[$rowProgramas['idPrograma']]['textoCustom'] = $arrayProgramaTmp['textoCustom'];
					$itemsProgramasModal2[$rowProgramas['idPrograma']]['imagen'] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['imagen'];
					$itemsProgramasModal2[$rowProgramas['idPrograma']]['imagenModal'] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['imagenModal'];
					$itemsProgramasModal2[$rowProgramas['idPrograma']]['logo'] = $config['site_url'].'/files/empresas/programas/'.$arrayProgramaTmp['icono'];

					if (trim($rowProgramas['telefonoPrograma']) != '') {
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['telefono'] = $rowProgramas['telefonoPrograma'];
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['telefono_n'] = onlyDigits($rowProgramas['telefonoPrograma']);
					}

					if (trim($rowProgramas['whatsappPrograma']) != '') {
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['whatsapp'] = $rowProgramas['whatsappPrograma'];
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['whatsapp_n'] = onlyDigits($rowProgramas['whatsappPrograma']);
					}

					if ($rowProgramas['videollamadasPrograma']) {
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['video_chat'] = true;	
					}

					if ($rowProgramas['iniciarvideollamadasPrograma'] && $render['btnIniciarVideollamada']) {
						$itemsProgramasModal2[$rowProgramas['idPrograma']]['iniciar_video_llamada'] = true;		
					}

            		$render['programa_'.$rowProgramas['idPrograma']] = true;

		            if (trim($rowProgramas['telefonoPrograma']) != '') {
			           	$render['programa_telefono_'.$rowProgramas['idPrograma']] = $rowProgramas['telefonoPrograma'];
			           	$render['programa_telefono_'.$rowProgramas['idPrograma'].'_n'] = onlyDigits($rowProgramas['telefonoPrograma']);
			        }

		            if (trim($rowProgramas['whatsappPrograma']) != '') {
		            	$render['programa_whatsapp_'.$rowProgramas['idPrograma']] = $rowProgramas['whatsappPrograma'];
		            	$render['programa_whatsapp_'.$rowProgramas['idPrograma'].'_n'] = onlyDigits($rowProgramas['whatsappPrograma']);
		            }

		            if ($rowProgramas['videollamadasPrograma']) {
						$render['programa_video_chat_'.$rowProgramas['idPrograma']] = true;		
		            }

		            if ($rowProgramas['iniciarvideollamadasPrograma'] && $render['btnIniciarVideollamada']) {
						$render['programa_iniciar_video_llamada_'.$rowProgramas['idPrograma']] = true;		
		            }

					$render['programa_informacionDiasHorarios_'.$rowProgramas['idPrograma']] = $rowProgramas['informacionDiasHorarios'];		

            			/**/
            			// $arrayPaisesProgramas = xmlFormat(getArray($config, 'idPais', "{$config['prefix']}rel_paises_programas", $row['idPais'], " and idPrograma = '{$rowProgramas['idPrograma']}'"));
            			// $render['programa_titulo_'.$rowProgramas['idPrograma']] = $arrayPaisesProgramas['titulo'];
            		$render['programa_titulo_previo_'.$rowProgramas['idPrograma']] = $rowProgramas['tituloPrevio'];
            		$render['programa_titulo_'.$rowProgramas['idPrograma']] = $rowProgramas['tituloPrograma'];
            			/**/

            		$itemsProgramas[$rowProgramas['idPrograma']]['id'] = $rowProgramas['idPrograma'];

						// horarios
					$stringCerrado = 'Cerrado';

					unset($aDays);
					unset($tmp);
					$queryTmp = $conn->prepare("select * from {$config['prefix']}horarios where idEmpresa = :idEmpresa and idPrograma = :idPrograma order by idDia, idHorario");
					$queryTmp->execute(array('idEmpresa' => $row['id'], 'idPrograma' => $rowProgramas['idPrograma']));
					$resTmp = $queryTmp->fetchAll();
					if (count($resTmp)) 
					{
						foreach ($resTmp as $rowTmp) 
						{
							$rowTmp = xmlFormat($rowTmp);

							/**/
							$start = strtotime(date("Y-m-d ".$rowTmp['desde'].':00'));
							$end = strtotime(date("Y-m-d ".$rowTmp['hasta'].':00'));

							if ($rowProgramas['duracionConsulta'] > 0) 	
							{
								while ($end > $start) 
								{
									$aDaysVideo[$rowProgramas['idPrograma']][$rowTmp['idDia']][] = date("H:i", $start);
									$start += $rowProgramas['duracionConsulta']*60;	
								}
							}

							/**/

							$rowTmp['desde'] = date("G:i", strtotime(date("Y-m-d ".$rowTmp['desde'].':00')));
							$rowTmp['hasta'] = date("G:i", strtotime(date("Y-m-d ".$rowTmp['hasta'].':00')));

							if (isset($tmp[$rowTmp['idDia']])) 
							{
								$tmp[$rowTmp['idDia']] .= ' / '.$rowTmp['desde'].' - '.$rowTmp['hasta'];
							} 
							else 
							{
								$tmp[$rowTmp['idDia']] = $rowTmp['desde'].' - '.$rowTmp['hasta'];
							}
						}

						$counter = 0;
						for ($i=1; $i <= 7; $i++) 
						{
							if ($i == 1) 
							{
								$aDays[$counter][] = $i;
							} 
							else 
							{
								if ($tmp[$i] == $tmp[$i-1]) 
								{
									$aDays[$counter][] = $i;
									// $tmp[$i-1] = '';
								} 
								else 
								{
									$counter++;
									$aDays[$counter][] = $i;
								}
							}
						}

						unset($items);
						foreach ($aDays as $key => $value) 
						{
							$str = '';
							$anterior = '';
							unset($itemsDias);
							foreach ($value as $idDia) 
							{
								if ($str == '') 
								{
									// $str .= "Dia $idDia ";
								}

								$itemsDias[] = '<strong>'.getStrDia($idDia).'</strong>';

								if (!$tmp[$idDia])
								{
									$tmp[$idDia] = $stringCerrado;
								}

								if ($anterior != $tmp[$idDia]) 
								{
									$str .= $tmp[$idDia];
								}

								if ($anterior == '') 
								{
									$anterior = $tmp[$idDia];
								}
							}
							$items[] = implode(', ', $itemsDias).': '.$str;
						}
						$render['programaHorario_'.$rowProgramas['idPrograma']] = $items;
					}            			
				}
			}

			$render['complementariosOk'] = $itemsProgramas2ok;
			$render['complementarios'] = $itemsProgramas2;
			$render['complementariosModal'] = $itemsProgramasModal2;





			$itemsinformaciones = array();
			$queryinformaciones = $conn->prepare("select * from {$config['prefix']}informacion where idEmpresa = {$row['id']}");
			$queryinformaciones->execute();
			$resinformaciones = $queryinformaciones->fetchAll();

			if (count($resinformaciones)) 
			{
				foreach ($resinformaciones as $rowinformaciones) 
				{
					$rowinformaciones = xmlFormat($rowinformaciones);

					$counterinformaciones++;

					// if ($counterProgramas > 12)
					// {
					// 	$counterProgramas = 1;
					// }

					$arrayinformacionTmp = xmlFormat(getArray($config, 'id', "{$config['prefix']}informacion", $rowinformaciones['id']));

					$itemsInformaciones[$rowinformaciones['id']]['id'] = $rowinformaciones['id'];
					$itemsInformaciones[$rowinformaciones['id']]['nombre_boton'] = $rowinformaciones['nombre_boton'];
					$itemsInformaciones[$rowinformaciones['id']]['type'] = $rowinformaciones['type'];
					$itemsInformaciones[$rowinformaciones['id']]['status'] = $rowinformaciones['status'];
					$itemsInformaciones[$rowinformaciones['id']]['imagen'] = $config['site_url'].'/files/empresas/informacion/imagen/'.$arrayinformacionTmp['imagen'];

					if($rowinformaciones['type'] == 1)
					{
						$itemsInformaciones[$rowinformaciones['id']]['contenido'] = $rowinformaciones['link'];
					}
					else if($rowinformaciones['type'] == 2)
					{
						$itemsInformaciones[$rowinformaciones['id']]['contenido'] = $config['site_url'].'/files/empresas/informacion/documentos/'.$arrayinformacionTmp['file'];
					}		
				}
				
			}

			$render['informacionOK'] = $itemsInformaciones;


			$queryProgramas = $conn->prepare("select * from {$config['prefix']}rel_empresas_programas where idEmpresa = '{$row['id']}'");
			$queryProgramas->execute();
			$resProgramas = $queryProgramas->fetchAll();
			if (count($resProgramas)) 
			{
				foreach ($resProgramas as $rowProgramas) 
				{
					$rowProgramas = xmlFormat($rowProgramas);

					$begin = new DateTime( date("Y-m-d") );
					$end = new DateTime( date("Y-m-d") );
					$end = $end->modify( '+1 year' ); 

					$interval = new DateInterval('P1D');
					$daterange = new DatePeriod($begin, $interval ,$end);

					$tmpFechas = array();
					foreach($daterange as $date) 
					{
						if (is_array($aDaysVideo[$rowProgramas['idPrograma']][$date->format("N")])) 
						{

							$fechas = implode(' ', $aDaysVideo[$rowProgramas['idPrograma']][$date->format("N")]).' ';

							if (is_array($itemsVideollamadas[$rowProgramas['idPrograma']][$date->format("Y-m-d")])) 
							{
								foreach ($itemsVideollamadas[$rowProgramas['idPrograma']][$date->format("Y-m-d")] as $key => $value) {
									$fechas = str_replace($value.' ', '', $fechas);
								}
							}

							$tmpFechas[] = array('dia' => $date->format("j"), 'mes' => $date->format("n")-1, 'anio' => $date->format("Y"), 'fechas' => trim($fechas));
						}
					}
            		$itemsProgramas[$rowProgramas['idPrograma']]['fechas'] = $tmpFechas;

					if ($_POST['submitVideollamada_'.$rowProgramas['idPrograma']]) 
					{
						// printArray($_POST); exit;

							// formulario
						unset($frmError);
						unset($validate);

						$validate[] = 'frmNombre_'.$rowProgramas['idPrograma'];
					    $validate[] = 'frmEmail_'.$rowProgramas['idPrograma'];
					    $validate[] = 'frmEmpresa_'.$rowProgramas['idPrograma'];
					    $validate[] = 'frmPais_'.$rowProgramas['idPrograma'];
					    $validate[] = 'frmFecha_'.$rowProgramas['idPrograma'];

						foreach ($validate as $v) 
						{
							if (trim($_POST[$v]) == '') 
							{
								$frmError = $language['translationCompleteFields'][$config['lang']];
							}
						}

						if (!$frmError) 
						{
							if (!checkMail($_POST['frmEmail_'.$rowProgramas['idPrograma']])) 
							{
								$frmError = $language['translationCompleteEmail'][$config['lang']];
							} 
							else 
							{

								$fechaPost = $_POST['frmFecha_'.$rowProgramas['idPrograma']];

								$tmpFecha = explode(' ', trim($_POST['frmFecha_'.$rowProgramas['idPrograma']]));
								$tmpFechaDate = explode('/', $tmpFecha[0]);

								if (!checkdate($tmpFechaDate[1], $tmpFechaDate[0], $tmpFechaDate[2])) 
								{
									$frmError = 'Fecha no valida';
								} 
								else 
								{
									$fechaDb = $tmpFechaDate[2].'-'.$tmpFechaDate[1].'-'.$tmpFechaDate[0].' '.$tmpFecha[1].':00';

									$queryVideollamadas = $conn->prepare("select * from {$config['prefix']}videollamadas_citas where idEmpresa = '{$row['id']}' and idPrograma = '{$rowProgramas['idPrograma']}' and frmFecha = '{$fechaDb}'");
									$queryVideollamadas->execute();
									$resVideollamadas = $queryVideollamadas->fetchAll();
									if (count($resVideollamadas)) 
									{
										$frmError = 'Fecha no disponible';
									} 
									else 
									{

											// $arrayPrograma = xmlFormat(getArray($config, 'id', "{$config['prefix']}programas", $rowProgramas['idPrograma']));
            								//$arrayPrograma = xmlFormat(getArray($config, 'idPais', "{$config['prefix']}rel_paises_programas", $row['idPais'], " and idPrograma = '{$rowProgramas['idPrograma']}'"));
											//$arrayPrograma['titulo'] = utf8_encode($arrayPrograma['titulo']);

							            unset($fields);
										$fields['fecha'] = date("Y-m-d H:i:s");
										$fields['ip'] = getIp();
										$fields['idEmpresa'] = $row['id'];
										$fields['nombreEmpresa'] = $row['titulo'];
										$fields['idPrograma'] = $rowProgramas['idPrograma'];
										$fields['nombrePrograma'] = $rowProgramas['tituloPrograma'];
										$fields['tituloPrevio'] = $rowProgramas['tituloPrevio'];
										$fields['frmNombre'] = $_POST['frmNombre_'.$rowProgramas['idPrograma']];
										$fields['frmEmail'] = $_POST['frmEmail_'.$rowProgramas['idPrograma']];
										$fields['frmEmpresa'] = $_POST['frmEmpresa_'.$rowProgramas['idPrograma']];
										$fields['frmPais'] = $_POST['frmPais_'.$rowProgramas['idPrograma']];
										$fields['frmFecha'] = $fechaDb;

										foreach ($_POST as $c => $v) 
										{
											if (!is_array($v))
											{
												$_POST[$c] = stripslashes(htmlspecialchars($v, ENT_QUOTES));
											}
										}

										$content = <<<EOD
										<b>Empresa:</b> {$fields['nombreEmpresa']} <br />
										<b>Programa:</b> {$fields['nombrePrograma']} <br />
										<hr />
										<b>Nombre y Apellido:</b> {$fields['frmNombre']} <br />
										<b>Email:</b> {$fields['frmEmail']} <br />
										<b>Empresa:</b> {$fields['frmEmpresa']} <br />
										<b>Pais:</b> {$fields['frmPais']} <br />
										<b>Fecha:</b> {$fechaPost} <br />
										EOD;

										$fields['html'] = $content;

										$stmt = $conn->prepare("insert into {$config['prefix']}videollamadas_citas (".implode(', ', array_keys($fields)).") values (".prepareFields($fields, 'insert').")");
										$stmt->execute(prepareFieldsArray($fields));

											// mail usuario
										$content = file_get_contents($_SERVER[DOCUMENT_ROOT].'/mail.html');
										$content = str_replace('{{site_url}}', $config['site_url'], $content);
										$content = str_replace('{{nombre}}', $fields['frmNombre'], $content);
										$content = str_replace('{{nombre_servicio}}', utf8_decode($fields['nombrePrograma']), $content);
										$content = str_replace('{{fecha}}', date("d/m/Y", strtotime($fields['frmFecha'])), $content);
										$content = str_replace('{{horario}}', date("H:i", strtotime($fields['frmFecha'])), $content);
										$content = str_replace('{{link_empresa}}', getLinkEmpresa($config, $row['slug']), $content);
										$content = str_replace('{{year}}', date("Y"), $content);

										unset($params);
										$params['fromMail'][$config['email_address']] = $config['siteName'];
										$params['subject'] = $config['siteName'].' - Reserva videollamada';
										$params['toMail'][$fields['frmEmail']] = stripslashes($fields['frmNombre']);
										$params['replyMail'][$config['email_address']] = $config['siteName'];
										$params['content'] = utf8_decode($content);

										sendEmail($config, $params);

											// mail administrador
										$content = file_get_contents($_SERVER[DOCUMENT_ROOT].'/mail-int.html');
										$content = str_replace('{{site_url}}', $config['site_url'], $content);
										$content = str_replace('{{nombre}}', $fields['frmNombre'], $content);
										$content = str_replace('{{email}}', $fields['frmEmail'], $content);
										$content = str_replace('{{empresa}}', $fields['frmEmpresa'], $content);
										$content = str_replace('{{pais}}', $fields['frmPais'], $content);
										$content = str_replace('{{fecha}}', date("d/m/Y", strtotime($fields['frmFecha'])), $content);
										$content = str_replace('{{horario}}', date("H:i", strtotime($fields['frmFecha'])), $content);
										$content = str_replace('{{year}}', date("Y"), $content);

										unset($params);
										$params['fromMail'][$config['email_address']] = $config['siteName'];
										$params['subject'] = $config['siteName'].' - Reserva videollamada';

										$queryTmpAdmin = $conn->prepare("select * from {$config['prefix']}empresas_admins, {$config['prefix']}rel_empresas_admins where {$config['prefix']}empresas_admins.id = {$config['prefix']}rel_empresas_admins.idAdmin and status = '1' and idEmpresa = '{$row['id']}'");
										$queryTmpAdmin->execute();
										$resTmpAdmin = $queryTmpAdmin->fetchAll();
										if (count($resTmpAdmin)) 
										{
											foreach ($resTmpAdmin as $rowTmpAdmin) 
											{
												$rowTmpAdmin = xmlFormat($rowTmpAdmin);
												$params['toMail'][$rowTmpAdmin['email']] = $rowTmpAdmin['nombre'].' '.$rowTmpAdmin['apellido'];
											}
										} 
										else 
										{
											$params['toMail'][$config['email_address']] = $config['siteName'];
										}

										$params['replyMail'][$fields['frmEmail']] = stripslashes($fields['frmNombre']);
										$params['content'] = utf8_decode($content);

										if (sendEmail($config, $params)) 
										{
											echo 'ok';
											exit;
										} 
										else 
										{
											$frmError = $language['translationErrorSendingEmail'][$config['lang']];
										}
									}
								}
					        }
						}

						if ($frmError != '')
						{
							echo $frmError;
						}

						exit;
					}            			
				}
			}
				
			$render['programas'] = $itemsProgramas;
//printArray($itemsProgramas);	
				// printArray($aDaysVideo);				
		}

	} 
	else 
	{

		$query = $conn->prepare("select * from {$config['prefix']}charlas where status = '1' and slug = :slug");
		$query->execute(array('slug' => $_GET['slug']));
		$res = $query->fetchAll();
		if (count($res)) 
		{
			$loadCharla = true;

			foreach ($res as $row) 
			{
				$row = xmlFormat($row);

	            $render['titulo_charla'] = $row['titulo'];
	            $render['header_charla'] = $config['site_url'].'/files/charlas/headers/'.$row['imagen'];
	            $render['fecha_charla'] = $row['fecha'];
	            $render['hora_charla'] = $row['hora'];
	            $render['donde_charla'] = nl2br($row['infoDonde']);
				$render['descripcion_charla'] = explode("<br />", removeNl(nl2br($row['descripcion'])));

	            $render['link_charla'] = getLinkCharla($config, $row['slug']);

					// formulario
				unset($frmError);
				unset($validate);

				if ($_POST['frmSubmitContact']) 
				{

				    $captchaResponse = json_decode(getFileContent('https://www.google.com/recaptcha/api/siteverify?secret='.$config['recaptchaSecret'].'&response='.$_POST['g-recaptcha-response'].'&remoteip='.getIp()), true);

					$validate[] = 'frmNombre';
					$validate[] = 'frmApellido';
				    $validate[] = 'frmEmail';
				    $validate[] = 'frmTelefonoArea';
				    $validate[] = 'frmTelefono';

					foreach ($validate as $v) 
					{
						if (trim($_POST[$v]) == '') {
							$frmError = $language['translationCompleteFields'][$config['lang']];
						}
					}

					$validate[] = 'frmNacimiento';
					$validate[] = 'frmNewsletter';

					if (!$frmError) 
					{
						if (!checkMail($_POST['frmEmail'])) 
						{
							$frmError = $language['translationCompleteEmail'][$config['lang']];
				        } 
				        elseif ($captchaResponse['success'] != 1) 
				        {
				            $frmError = $language['translationIncorrectCaptcha'][$config['lang']];
						} 
						else 
						{

				            unset($fields);
							$fields['fecha'] = date("Y-m-d H:i:s");
							$fields['ip'] = getIp();
							$fields['idCharla'] = $row['id'];
							$fields['tituloCharla'] = $row['titulo'];
							$fields['frmNombre'] = $_POST['frmNombre'];
							$fields['frmApellido'] = $_POST['frmApellido'];
							$fields['frmEmail'] = $_POST['frmEmail'];
							$fields['frmTelefonoArea'] = $_POST['frmTelefonoArea'];
							$fields['frmTelefono'] = $_POST['frmTelefono'];
							$fields['frmNacimiento'] = $_POST['frmNacimiento'];
							$fields['frmNewsletter'] = abs($_POST['frmNewsletter']);

							foreach ($_POST as $c => $v) 
							{
								if (!is_array($v))
								{
									$_POST[$c] = stripslashes(htmlspecialchars($v, ENT_QUOTES));
								}
							}

							$frmNewsletter = $_POST['frmNewsletter'] ? 'Si' : 'No';

							$content = <<<EOD
							<b>Nombre y Apellido:</b> {$_POST['frmNombre']} {$_POST['frmApellido']}<br />
							<b>Email:</b> {$_POST['frmEmail']} <br />
							<b>Telefono:</b> {$_POST['frmTelefonoArea']} {$_POST['frmTelefono']} <br />
							<b>Fecha Nacimiento:</b> {$_POST['frmNacimiento']} <br />
							<b>Acepto recibir información:</b> {$frmNewsletter} <br />
							EOD;

							$fields['html'] = $content;

							$content = getFileContent($config['site_url'].'/charlas/email.html');
							$content = str_replace('{{header_charla}}', $render['header_charla'], $content);
							$content = str_replace('{{titulo_charla}}', $render['titulo_charla'], $content);
							$content = str_replace('{{fecha_charla}}', $render['fecha_charla'], $content);
							$content = str_replace('{{hora_charla}}', $render['hora_charla'], $content);
							$content = str_replace('{{infoUrl_charla}}', $row['infoUrl'], $content);

							if (trim($row['infoTexto']) != '') 
							{
								$content = str_replace('{{infoTexto_charla}}', '<p style="font-family: sans-serif; font-weight: 400; font-size: 14px; color: #333333; line-height: 1.6em; text-align: center;">'.$row['infoTexto'].'</p><br><br>', $content);
							} 
							else 
							{
								$content = str_replace('{{infoTexto_charla}}', '', $content);
							}
								
							$stmt = $conn->prepare("insert into {$config['prefix']}forms_charlas (".implode(', ', array_keys($fields)).") values (".prepareFields($fields, 'insert').")");
							$stmt->execute(prepareFieldsArray($fields));

							unset($params);
							$params['fromMail'][$config['email_address']] = $config['siteName'];
							$params['subject'] = $config['siteName'].' - '.utf8_decode($render['titulo_charla']);
							$params['toMail'][$fields['frmEmail']] = stripslashes($fields['frmNombre'].' '.$fields['frmApellido']);
							$params['replyMail'][$config['email_address']] = $config['siteName'];
							$params['content'] = utf8_decode($content);

							if (sendEmail($config, $params)) 
							{
								header("location: ".$render['link_charla'].'/gracias');
								exit;
							} 
							else 
							{
								$frmError = $language['translationErrorSendingEmail'][$config['lang']];
							}
				        }
					}
				}

				if ($frmError) 
				{
					$render['frmMessage'] = $frmError;
				} 
				elseif ($_GET['msg']) 
				{
					$render['frmMessageThanks'] = true;
				}

				if (is_array($validate)) 
				{
					foreach ($validate as $c => $v) 
					{
						$render[$v] = $_POST[$v];
					}
				}

				$render['recaptchaPublic'] = $config['recaptchaPublic'];	            	
			}
		}
	}
}

if (!$loadLanding && !$loadCharla) 
{
	$tableId = 'id';
	$query = $conn->prepare("select * from {$config['prefix']}empresas where status = '1' order by titulo");
	$query->execute();
	$res = $query->fetchAll();
	if (count($res)) 
	{
		foreach ($res as $row) 
		{
			$row = xmlFormat($row);
			$items[$row[$tableId]]['title'] = $row['titulo'];
			$items[$row[$tableId]]['link'] = getLinkEmpresa($config, $row['slug']);
		}
		$render['items'] = $items;
	}
}

if ($loadLanding) 
{
	if ($_GET['demo'])
	{
		echo $twig->render('template_landing2.html', $render);
	}
	else
	{
		echo $twig->render('template_landing.html', $render);
	}

} 
elseif ($loadCharla) 
{
	if ($_GET['gracias']) 
	{
		echo $twig->render('charlas/template_gracias.html', $render);		
	} 
	else 
	{
		echo $twig->render('charlas/template_index.html', $render);		
	}
} 
else 
{
	echo $twig->render('template_index.html', $render);
}



function getLinkEmpresa($config, $slug) {
	$conn = getDbConnection($config);
	return $config['site_url'].'/'.$slug;
}

function getLinkCharla($config, $slug) {
	$conn = getDbConnection($config);
	return $config['site_url'].'/'.$slug;
}

//printArray($render['complementarios']);
//printArray($render['complementariosModal']);

?>