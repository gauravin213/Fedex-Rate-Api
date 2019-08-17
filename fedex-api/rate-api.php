<?php
// Copyright 2009, FedEx Corporation. All rights reserved.
	// Version 12.0.0

	require_once('fedex-common.php5');

	$newline = "<br />";
	//The WSDL is not included with the sample code.
	//Please include and reference in $path_to_wsdl variable.

	$path = get_stylesheet_directory_uri().'/fedex-api/Rate';

	/**/
	$path_to_wsdl = $path.'/RateService_v16.wsdl';

	ini_set("soap.wsdl_cache_enabled", "0");
	 
	$opts = array(
		  'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)
		);
	$client = new SoapClient($path_to_wsdl, array('trace' => 1,'stream_context' => stream_context_create($opts)));  
	// Refer to http://us3.php.net/manual/en/ref.soap.php for more information

	$request['WebAuthenticationDetail'] = array(
		'UserCredential' => array(
			'Key' => '', 
			'Password' => '', 
		)
	); 
	$request['ClientDetail'] = array(
		'AccountNumber' => '',
		'MeterNumber' => '',
	);
	$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request using PHP ***');
	$request['Version'] = array(
		'ServiceId' => 'crs', 
		'Major' => '16', 
		'Intermediate' => '0', 
		'Minor' => '0'
	);
	//$request['ReturnTransitAndCommit'] = true;
	$request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
	$request['RequestedShipment']['ShipTimestamp'] = date('c');
	//$request['RequestedShipment']['ServiceType'] = 'INTERNATIONAL_PRIORITY'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
	$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
	/*$request['RequestedShipment']['TotalInsuredValue']=array(
		'Ammount'=>100,
		'Currency'=>'USD'
	);*/

	$request['RequestedShipment']['Shipper'] = array
                (
                    'Address' => array
                        (
                            'PostalCode' => '94534',
                            'CountryCode' => 'US'
                        ),

                );
	$request['RequestedShipment']['Recipient'] = array
                (
                    'Address' => array
                        (
                            'StreetLines' => array
                                (
                                    '0' => '4747 Central Way',
                                    '1' => ''
                                ),

                            'Residential' => '',
                            'PostalCode' => '94534',
                            'City' => 'FAIRFIELD',
                            'StateOrProvinceCode' => 'CA',
                            'CountryCode' => 'US',
                        )

                );
	$request['RequestedShipment']['ShippingChargesPayment'] = array
                (
                    'PaymentType' => 'SENDER',
                    'Payor' => array
                        (
                            'ResponsibleParty' => array
                                (
                                    'AccountNumber' => '578539581',
                                    'CountryCode' => 'US'
                                ),

                        ),

                );
	$request['RequestedShipment']['RateRequestTypes'] = 'NONE';
	$request['RequestedShipment']['PackageCount'] = '1';
	$request['RequestedShipment']['RequestedPackageLineItems'] = array
                (
                    '0' => array
                        (
                            'SequenceNumber' => '1',
                            'GroupNumber' => '1',
                            'GroupPackageCount' => '1',
                            'Weight' => array
                                (
                                    'Value' => '2.54',
                                    'Units' => 'LB'
                                ),

                            'Dimensions' => array
                                (
                                    'Length' => '9',
                                    'Width' => '8',
                                    'Height' => '6',
                                    'Units' => 'IN'
                                ),

                        )

                );
                
                
    	try {
		if(setEndpoint('changeEndpoint')){
			$newLocation = $client->__setLocation(setEndpoint('endpoint'));
		}
		
		$response = $client -> getRates($request);

	
	        
	    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){  	
	    	$rateReplys = $response -> RateReplyDetails;


	    	

	    	echo "<pre>";
	    	print_r($rateReplys);
	    	echo "</pre>";


	    	$rateReplys_sort = array();


	        $htm = '';
	        $htm .='<table border="1">';
	        $htm .= '<tr><td>Service Type</td><td>Amount</td></tr>';

		    foreach ($rateReplys as $rateReply) {

		    	$htm1 = '';
		    	
		    	$htm1 .= '<tr>';

				$serviceType = $rateReply -> ServiceType;

				$serviceType = str_replace("_"," ", $serviceType);
					
		    	if($rateReply->RatedShipmentDetails && is_array($rateReply->RatedShipmentDetails)){
		    	    
		    	    $amount2 = number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",");

		    	   $amount = '<td>$' . number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") . '</td>';
				}elseif($rateReply->RatedShipmentDetails && ! is_array($rateReply->RatedShipmentDetails)){
					$amount = '<td>$' . number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") . '</td>';
					
					$amount2 = number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",");
		
					
				}
		        if(array_key_exists('DeliveryTimestamp',$rateReply)){
		        	$deliveryDate= '<td>' . $rateReply->DeliveryTimestamp . '</td>';
		        }else if(array_key_exists('TransitTime',$rateReply)){
		        	$deliveryDate= '<td>' . $rateReply->TransitTime . '</td>';
		        }else {
		        	//$deliveryDate='<td>&nbsp;</td>';
		        }
		        $htm1 .= '<td><input type="radio" name="admin_shipping_rates" id="admin_shipping_rates" value="'.$amount2.'">'.$serviceType .'</td>'. $amount. $deliveryDate;
		        $htm1 .= '</tr>';


		        $rateReplys_sort[$amount2] = $htm1;
			}

			ksort($rateReplys_sort);
			$htm.= implode('',$rateReplys_sort);
	    	
	        $htm .= '</table>';


	        echo $htm;



		
	        
	        
	        

	        
	        //printSuccess($client, $response);
	    }else{
	        //printError($client, $response);
	    } 
	    //writeToLog($client);    // Write to log file   
	} catch (SoapFault $exception) {
	   //printFault($exception, $client);        
	}
	/**/
