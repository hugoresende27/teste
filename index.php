<?php
require_once '../../functions.php';

session_start();

//TESTES GIT HUB

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$l = $_GET["l"];
$start = isset($_GET['start']) ? htmlspecialchars(strip_tags($_GET['start'])) : 0;
$end = isset($_GET['end']) ? htmlspecialchars(strip_tags($_GET['end'])) : 1;

$date = date('Y/m/d H:i:s');

$path = 'http://feed.crmhcpro.pt/xml/bpi/index.php';

$xml = new XMLWriter();

$companies_to_export = getCompaniesToExportBPI('bpi', $l);

if ($companies_to_export) {

	foreach ($companies_to_export as $company) {

		$getClientData = getClientData($company["empresa_id"]);
		
		?><h2><?=$getClientData["nome_social"];?></h2><?php

		$total = getPendingPropertiesTotal(2, $company["empresa_id"]);
		$total = json_decode($total, true);
		if ($total["total"] > 0) {

			$properties = getPendingProperties(2, $company["empresa_id"], $start, $end);
			if ($properties) {
				//xml structure
					$xml->openUri('company_'.$company["empresa_id"].'_'.date('Y').date('m').date('d').date('hms').'.xml');
					$xml->startDocument('1.0', 'utf-8');
					$xml->startElement('Transferencia');

					// attributes for Transferencia element
					$xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
					$xml->writeAttribute('IdRemetente', $company["api_key"]);
					$xml->writeAttribute('IdTransferencia', '1');
					$xml->writeAttribute('NumeroSequencia', '1');
					$xml->writeAttribute('NumeroTotalFicheiros', $total["total"]);
					$xml->writeAttribute('xsi:noNamespaceSchemaLocation', 'ImoveisPTD.xsd');

					// start Parceiro element
					$xml->startElement('Parceiro');

					// attributes for Parceiro element
					$xml->writeAttribute('CodigoReportante', $company["api_key"]);
					$xml->writeAttribute('IdExterno', $company["empresa_id"]);
					
					// start InformacaoParceiro element
					$xml->startElement('InformacaoParceiro');
						$xml->startElement('Nome');$xml->writeCData($getClientData["nome_social"]);$xml->endElement();
						$xml->startElement('Morada');$xml->writeCData($getClientData["morada_rua"]);$xml->endElement();
						
						// start Contactos element
						$xml->startElement('Contactos');
							$email_id = array_search('email', array_column($getClientData['contacts'], 'tipo_contacto'));
							$email = $getClientData['contacts'][$email_id]['contacto'];
							$website = $getClientData['websites'][0]['website'];
							$xml->startElement('Email');$xml->writeCData($email);$xml->endElement();
							$xml->startElement('WebSite');$xml->writeCData($website);$xml->endElement();
						// end Contactos element
						$xml->endElement();
					// end InformacaoParceiro element
					$xml->endElement();

					// start Imoveis element
					$xml->startElement('Imoveis');

					$faltam = ($total["total"] - ($start + 5));
					if ($faltam < 0) {$faltam = 0;}
					?>
					
					<h3><?='<b>' . $total["total"] . '</b> imóveis para exportar';?></h3>
					<h3><?='Faltam <b>' . $faltam . '</b> imóveis';?></h3>
					
					<?php
					foreach ($properties as $property) {
	    				$getPropertyData = getPropertyData($property['empresa_id'], $property['imovel_id']);

	    				// change some fields, according to portal's specs
		    				$nome_distrito = $getPropertyData["morada_distrito"];
		    				$nome_concelho = $getPropertyData["morada_concelho"];

		    				if ($nome_distrito == "Madeira") {$nome_distrito = "Ilha da Madeira";}
		    				if ($nome_distrito == "Açores" && $nome_concelho == "Vila do Porto") {$nome_distrito = "Ilha de Santa Maria";}
		    				if ($nome_distrito == "Açores" && ($nome_concelho == "Lagoa (Açores)" || $nome_concelho == "Nordeste" || $nome_concelho == "Ponta Delgada" || $nome_concelho == "Povoação" || $nome_concelho == "Ribeira Grande" || $nome_concelho == "Vila Franca do Campo")) {$nome_distrito = "Ilha de São Miguel";}
		    				if ($nome_distrito == "Açores" && ($nome_concelho == "Vila Praia da Vitória" || $nome_concelho == "Angra do Heroísmo")) {$nome_distrito = "Ilha Terceira";}
		    				if ($nome_distrito == "Açores" && $nome_concelho == "Santa Cruz da Graciosa") {$nome_distrito = "Ilha da Graciosa";}
		    				if ($nome_distrito == "Açores" && ($nome_concelho == "Velas" || $nome_concelho == "Calheta (Açores)")) {$nome_distrito = "Ilha de São Jorge";}
		    				if ($nome_distrito == "Açores" && ($nome_concelho == "Madalena" || $nome_concelho == "São Roque do Pico" || $nome_concelho == "Lajes do Pico")) {$nome_distrito = "Ilha do Pico";}
		    				if ($nome_distrito == "Açores" && $nome_concelho == "Horta") {$nome_distrito = "Ilha do Faial";}
		    				if ($nome_distrito == "Açores" && ($nome_concelho == "Santa Cruz das Flores" || $nome_concelho == "Lajes das Flores")) {$nome_distrito = "Ilha das Flores";}
		    				if ($nome_distrito == "Açores" && $nome_concelho == "Vila do Corvo") {$nome_distrito = "Ilha do Corvo";}
		    				if ($nome_distrito == "Vila real") {$nome_distrito = "Vila Real";}
		    				if ($nome_distrito == "Castelo branco") {$nome_distrito = "Castelo Branco";}
		    				if ($nome_distrito == "Viana do castelo") {$nome_distrito = "Viana do Castelo";}

		    				$classe_energetica = $getPropertyData["classe_energetica"];
		    				if ($classe_energetica == "" || $classe_energetica == "Em execução" || $classe_energetica == "Não disponível" || $classe_energetica == "Não aplicável" || $classe_energetica == "Isento" || $classe_energetica == "Não se aplica") {
		    					$classe_energetica = "unknown";
		    				}

		    				$estado_imovel = $getPropertyData["estado_imovel"];
		    				if ($estado_imovel == 'Em Construção' || $estado_imovel == 'Viabilidade de Construção' || $estado_imovel == 'Em Projecto') {
		    				    $estado_imovel = "Em Construção";
		    				}
		    				elseif ($estado_imovel == 'Novo' || $estado_imovel == 'Excelente') {
		    				    $estado_imovel = "Novo";
		    				}
		    				elseif ($estado_imovel == 'Por Recuperar') {
		    				    $estado_imovel = "Para Recuperar";
		    				}
		    				elseif ($estado_imovel == 'Recuperado' || $estado_imovel == 'Remodelado') {
		    				    $estado_imovel = "Recuperado";
		    				}
		    				elseif ($estado_imovel == 'Ruína') {
		    				    $estado_imovel = "Em Obras";
		    				}
		    				elseif ($estado_imovel == 'Em uso' || $estado_imovel == 'Habitável' || $estado_imovel == 'Razoável' || $estado_imovel == 'Semi-Novo' || $estado_imovel == 'Usado') {
		    				    $estado_imovel = "Usado";
		    				}

						// start Imovel element
						$xml->startElement('Imovel');

							// attributes for Imovel element
							$xml->writeAttribute('RefInterna', $getPropertyData["reference"]);
							$xml->writeAttribute('RefExterna', $getPropertyData["reference"]);

							// If action = remove
							if ($property['action'] == "remove") {
								$xml->writeAttribute('Operacao', 'Apagar');
							}
							
							// If action = export
							else {
								$xml->writeAttribute('Operacao', 'Alterar');

								// start Localizacao element
								$xml->startElement('Localizacao');
									// start and end Distrito element
									$xml->startElement('Distrito');$xml->writeCData($nome_distrito);$xml->endElement();
									// start and end Concelho element
									$xml->startElement('Concelho');$xml->writeCData($nome_concelho);$xml->endElement();
									// start and end Freguesia element
									$xml->startElement('Freguesia');$xml->writeCData($getPropertyData["morada_freguesia"]);$xml->endElement();
									// start and end MostrarMorada element
									$xml->startElement('MostrarMorada');$xml->writeCData('0');$xml->endElement();
								// end Localizacao element
								$xml->endElement();

								// start Negocio element
								$xml->startElement('Negocio');
									// start and end TipoOperacao element
									$xml->startElement('TipoOperacao');$xml->writeCData($getPropertyData["business"]);$xml->endElement();
									// start and end Preco element
									if ($getPropertyData["business"] == "Arrendamento" || $getPropertyData["business"] == "Arrendamento para Férias" || $getPropertyData["business"] == "Arrendamento Temporário") {
										$xml->startElement('Preco');$xml->writeCData($getPropertyData["rent_price"]);$xml->endElement();
									} else{
										$xml->startElement('Preco');$xml->writeCData($getPropertyData["sell_price"]);$xml->endElement();
									}
									// start and end MostrarPreco element
									$xml->startElement('MostrarPreco');$xml->writeCData($getPropertyData["show_price"]);$xml->endElement();
								// end Negocio element
								$xml->endElement();

								if ($estado_imovel != 'Não se Aplica' && $estado_imovel != 'Não Disponível') {
									// start and end EstadoImovel element
									$xml->startElement('EstadoImovel');$xml->writeCData($estado_imovel);$xml->endElement();
								}

								// start InfoGeral element
								$xml->startElement('InfoGeral');
									// start and end TipoImovel element
									$xml->startElement('TipoImovel');$xml->writeCData($getPropertyData["type"]);$xml->endElement();

									if ($getPropertyData["tipologia"] != "Não se aplica") {
										// start and end Tipologia element
										$xml->startElement('Tipologia');$xml->writeCData($getPropertyData["tipologia"]);$xml->endElement();
									}
									// start and end AnoConstrucao element
									if ($getPropertyData["year"] != "") {
										$xml->startElement('AnoConstrucao');$xml->writeCData($getPropertyData["year"]);$xml->endElement();
									}
									// start and end Piso element
									// $xml->startElement('Piso');$xml->writeCData($getPropertyData["andar"]);$xml->endElement();
								// end InfoGeral element
								$xml->endElement();

								// start AreasImovel element
								$xml->startElement('AreasImovel');
									// start and end AreaImovel element
									$xml->startElement('AreaImovel');$xml->writeAttribute('TipoArea', 'Útil');$xml->writeCData($getPropertyData["area_util"]);$xml->endElement();
									// start and end AreaImovel element
									$xml->startElement('AreaImovel');$xml->writeAttribute('TipoArea', 'Bruta');$xml->writeCData($getPropertyData["area_bruta"]);$xml->endElement();
									// start and end AreaImovel element
									$xml->startElement('AreaImovel');$xml->writeAttribute('TipoArea', 'Terreno');$xml->writeCData($getPropertyData["area_terreno"]);$xml->endElement();
								// end AreasImovel element
								$xml->endElement();

								// start Multimedias element
								if (!empty($getPropertyData['multimedia'])) {

									$xml->startElement('Multimedias');
										$y = 1;
										foreach ($getPropertyData['multimedia']["foto"] as $fotoItem) {

											// Enviar imagens como Ficheiro BASE64
												/*
												$arrContextOptions=array(
												    "ssl"=>array(
												        "verify_peer"=>false,
												        "verify_peer_name"=>false,
												    ),
												);

												// Get the image and convert into string
												$fotoImovel = file_get_contents("https://crmhcpro.pt/uploads/".$property['empresa_id']."/properties/".$property['imovel_id']."/foto/watermark/".$fotoItem["path"], false, stream_context_create($arrContextOptions));
												$fotoImovel = base64_encode($fotoImovel);

												// start Multimedia element
												$xml->startElement('Multimedia');$xml->writeAttribute('Nome', $fotoItem["titulo"]);$xml->writeAttribute('TipoMultimedia', 'Foto');
													// start and end DescricaoMultimedia element
													$xml->startElement('DescricaoMultimedia');$xml->writeCData($fotoItem["titulo"]);$xml->endElement();
													if ($y == 1) {
														$xml->startElement('Ficheiro');$xml->writeAttribute('Encoding', 'base64');$xml->writeAttribute('MIME', 'image/jpeg');$xml->writeAttribute('Principal', 'true');$xml->writeCData($fotoImovel);$xml->endElement();
													} else {
														$xml->startElement('Ficheiro');$xml->writeAttribute('Encoding', 'base64');$xml->writeAttribute('MIME', 'image/jpeg');$xml->writeCData($fotoImovel);$xml->endElement();
													}
												// end Multimedia element
												$xml->endElement();
												*/

											// Enviar imagens como URL

												if ($company["empresa_id"] == 6142) {
													$fotoImovel = "https://crmhcpro.pt/uploads/".$property['empresa_id']."/properties/".$property['imovel_id']."/foto/original/".$fotoItem["path"];
												} else {
													$fotoImovel = "https://crmhcpro.pt/uploads/".$property['empresa_id']."/properties/".$property['imovel_id']."/foto/watermark/".$fotoItem["path"];
												}

												// start Multimedia element
												$xml->startElement('Multimedia');$xml->writeAttribute('Nome', $fotoItem["titulo"]);$xml->writeAttribute('TipoMultimedia', 'Foto');
													$xml->startElement('URL');$xml->writeCData($fotoImovel);$xml->endElement();
												// end Multimedia element
												$xml->endElement();

											if ($y == 30) {break;}
											$y ++;
										}
									// end Multimedias element
									$xml->endElement();
								}

								// start and end Observacoes element
								$descricao = $getPropertyData['descriptions'][0]["descricao"];
								$xml->startElement('Observacoes');$xml->writeCData($descricao);$xml->endElement();

								/*
								$hcpro_caracteristicas = $getPropertyData['characteristics'];

								//----------------------------------------
								// Casas de Banho
								//----------------------------------------	
								foreach ($hcpro_caracteristicas as $caracteristica) {
									
									if ($caracteristica["name"] == "Nº Casas de Banho") {
										// start Caracteristicas element
										$xml->startElement('Caracteristicas');
											// start and end Caracteristica element
											$xml->startElement('Caracteristica');$xml->writeAttribute('NomeCaracteristica', 'Nº Casas de Banho');$xml->writeCData($caracteristica["value"]);$xml->endElement();
										// end Caracteristicas element
										$xml->endElement();
									}
									
								}
								*/
								
								// start and end CertEnerg element
								$xml->startElement('CertEnerg');$xml->writeCData($classe_energetica);$xml->endElement();

								
							}

	        			// end Imovel element
	        			$xml->endElement();

	        			setPendingPropertyProcessed(2, $property['imovel_id']);
					}
				// end Imoveis element
				$xml->endElement();
				
				// end Parceiro element
				$xml->endElement();

				// end Transferencia element
				$xml->endElement();
				
				$url = $path.'?l='.$l.'&start='.($start+5).'&end='.$end;
				echo '<meta HTTP-EQUIV="Refresh" CONTENT="1; url='.$url.'">';
			}
			else {
				deleteProcessededPropertiesBPI('2', $company["empresa_id"]); //BPI

				?>
				<h2>Exportação empresa concluída!</h2>
				<?php

				$url = $path.'?l='.($l+1).'&start=0&end='.$end;
				echo '<meta HTTP-EQUIV="Refresh" CONTENT="1; url='.$url.'">';
			}

		}
		else {
			?>
			<h2>Cliente não tem imóveis para exportar!</h2>
			<?php

			$url = $path.'?l='.($l+1).'&start=0&end='.$end;
			echo '<meta HTTP-EQUIV="Refresh" CONTENT="1; url='.$url.'">';
		}

	}


}
else {
	header('location: https://feed.crmhcpro.pt/xml/bpi/upload.php');
}